<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentLine;
use App\Models\Depot;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockMovementService
{
    /**
     * Document type codes that REDUCE stock (outbound)
     */
    private const OUTBOUND_TYPES = ['BL', 'FA', 'BR']; // Bon de Livraison, Facture, Bon de Retour

    /**
     * Document type codes that INCREASE stock (inbound)
     */
    private const INBOUND_TYPES = ['BC']; // Bon de Commande (when it's a purchase)

    /**
     * Documents that do NOT affect stock
     */
    private const NON_STOCK_TYPES = ['DE']; // Devis (quotes)

    /**
     * Process stock movements when a document is created or modified
     *
     * @param Document $document
     * @param array|null $oldLines Previous document lines (for updates)
     * @return void
     */
    public function processDocumentMovement(Document $document, ?array $oldLines = null): void
    {
        // Determine movement type based on document type code
        $movementType = $this->getMovementType($document->type_document_code);

        // Don't process if document type doesn't affect stock
        if ($movementType === null) {
            return;
        }

        // Get or create default depot if not specified
        $depot = $document->depot ?? $this->getDefaultDepot();

        if (!$depot) {
            return; // Can't process without a depot
        }

        DB::transaction(function () use ($document, $depot, $movementType, $oldLines) {
            // If updating, reverse old movements first
            if ($oldLines !== null) {
                foreach ($oldLines as $oldLine) {
                    $this->reverseLineMovement(
                        $document,
                        $oldLine,
                        $depot,
                        $movementType
                    );
                }
            }

            // Process new/current movements
            foreach ($document->lines as $line) {
                $this->processLineMovement(
                    $document,
                    $line,
                    $depot,
                    $movementType
                );
            }
        });
    }

    /**
     * Process a single document line's stock movement
     */
    private function processLineMovement(
        Document $document,
        DocumentLine $line,
        Depot $depot,
        string $movementType
    ): void {
        $direction = $this->getMovementDirection($movementType);
        $quantity = (float) $line->dl_qte * $direction;

        // Update or create stock record
        $stock = Stock::firstOrCreate(
            [
                'article_id' => $line->article_id,
                'depot_id' => $depot->id,
            ],
            [
                'stock_reel' => 0,
                'stock_reserve' => 0,
            ]
        );

        // Update stock
        $stock->update([
            'stock_reel' => max(0, (float) $stock->stock_reel + $quantity),
        ]);

        // Create audit trail
        StockMovement::create([
            'article_id' => $line->article_id,
            'depot_id' => $depot->id,
            'movement_type' => $this->mapMovementType($movementType),
            'quantity' => abs($quantity),
            'reference' => $document->do_piece,
            'reference_type' => $document->type_document_code,
            'reference_id' => $document->id,
            'user_id' => auth()->id(),
            'notes' => "Document {$document->type_document_code} {$document->do_piece}",
        ]);
    }

    /**
     * Reverse a line movement (for document updates/deletes)
     */
    private function reverseLineMovement(
        Document $document,
        array $oldLine,
        Depot $depot,
        string $movementType
    ): void {
        $direction = $this->getMovementDirection($movementType);
        $quantity = (float) $oldLine['dl_qte'] * $direction * -1; // Reverse direction

        // Update stock
        $stock = Stock::where([
            'article_id' => $oldLine['article_id'],
            'depot_id' => $depot->id,
        ])->first();

        if ($stock) {
            $stock->update([
                'stock_reel' => max(0, (float) $stock->stock_reel + $quantity),
            ]);
        }

        // Create audit trail for reversal
        StockMovement::create([
            'article_id' => $oldLine['article_id'],
            'depot_id' => $depot->id,
            'movement_type' => 'ADJUSTMENT',
            'quantity' => abs($quantity),
            'reference' => $document->do_piece,
            'reference_type' => $document->type_document_code,
            'reference_id' => $document->id,
            'user_id' => auth()->id(),
            'notes' => "Reversal: Document {$document->type_document_code} {$document->do_piece}",
        ]);
    }

    /**
     * Delete document stock movements (reverse all associated movements)
     */
    public function deleteDocumentMovements(Document $document): void
    {
        $movementType = $this->getMovementType($document->type_document_code);

        if ($movementType === null) {
            return;
        }

        $depot = $document->depot ?? $this->getDefaultDepot();
        if (!$depot) {
            return;
        }

        DB::transaction(function () use ($document, $depot, $movementType) {
            foreach ($document->lines as $line) {
                $direction = $this->getMovementDirection($movementType);
                $quantity = (float) $line->dl_qte * $direction * -1; // Reverse

                // Update stock
                $stock = Stock::where([
                    'article_id' => $line->article_id,
                    'depot_id' => $depot->id,
                ])->first();

                if ($stock) {
                    $stock->update([
                        'stock_reel' => max(0, (float) $stock->stock_reel + $quantity),
                    ]);
                }

                // Create audit trail
                StockMovement::create([
                    'article_id' => $line->article_id,
                    'depot_id' => $depot->id,
                    'movement_type' => 'ADJUSTMENT',
                    'quantity' => abs($quantity),
                    'reference' => $document->do_piece,
                    'reference_type' => $document->type_document_code,
                    'reference_id' => $document->id,
                    'user_id' => auth()->id(),
                    'notes' => "Document deleted: {$document->type_document_code} {$document->do_piece}",
                ]);
            }
        });
    }

    /**
     * Manual stock adjustment
     */
    public function adjustStock(Stock $stock, float $newQuantity, string $reason = ''): void
    {
        $oldQuantity = (float) $stock->stock_reel;
        $difference = $newQuantity - $oldQuantity;

        DB::transaction(function () use ($stock, $newQuantity, $difference, $reason) {
            $stock->update(['stock_reel' => max(0, $newQuantity)]);

            StockMovement::create([
                'article_id' => $stock->article_id,
                'depot_id' => $stock->depot_id,
                'movement_type' => 'ADJUSTMENT',
                'quantity' => abs($difference),
                'user_id' => auth()->id(),
                'notes' => $reason ?: 'Manual stock adjustment',
            ]);
        });
    }

    /**
     * Determine if movement is inbound or outbound
     * Returns: 'IN' or 'OUT' or null if non-tracking type
     */
    private function getMovementType(string $typeCode): ?string
    {
        if (in_array($typeCode, self::INBOUND_TYPES)) {
            return 'IN';
        }

        if (in_array($typeCode, self::OUTBOUND_TYPES)) {
            return 'OUT';
        }

        return null;
    }

    /**
     * Get direction multiplier: +1 for IN, -1 for OUT
     */
    private function getMovementDirection(string $movementType): int
    {
        return $movementType === 'IN' ? 1 : -1;
    }

    /**
     * Map internal movement type to database enum
     */
    private function mapMovementType(string $movementType): string
    {
        return $movementType === 'IN' ? 'IN' : 'OUT';
    }

    /**
     * Get default depot (first one or configured)
     */
    private function getDefaultDepot(): ?Depot
    {
        return Depot::orderBy('id')->first();
    }
}
