<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Document;
use App\Models\DocumentLine;
use App\Models\Depot;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Support\DocumentTypeRegistry;
use Illuminate\Support\Facades\DB;

class StockMovementService
{
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
        $newStock = (float) $stock->stock_reel + $quantity;
        if ($newStock < 0) {
            throw new InsufficientStockException("Insufficient stock for article {$line->article_id} in depot {$depot->id}.");
        }
        $stock->update(['stock_reel' => $newStock]);

        // Create audit trail
        StockMovement::create([
            'article_id' => $line->article_id,
            'depot_id' => $depot->id,
            'movement_type' => $this->mapMovementType($movementType),
            'type' => $movementType === 'IN' ? 'entree' : ($movementType === 'OUT' ? 'sortie' : 'adjustment'),
            'quantity' => abs($quantity),
            'reference' => $document->do_piece,
            'reference_type' => $document->type_document_code,
            'reference_id' => $document->id,
            'source_type' => $document->type_document_code,
            'source_id' => $document->id,
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
            $newStock = (float) $stock->stock_reel + $quantity;
            if ($newStock < 0) {
                throw new InsufficientStockException("Invalid reversal causing negative stock for article {$oldLine['article_id']} in depot {$depot->id}.");
            }
            $stock->update(['stock_reel' => $newStock]);
        }

        // Create audit trail for reversal
        StockMovement::create([
            'article_id' => $oldLine['article_id'],
            'depot_id' => $depot->id,
            'movement_type' => 'ADJUSTMENT',
            'type' => 'adjustment',
            'quantity' => abs($quantity),
            'reference' => $document->do_piece,
            'reference_type' => $document->type_document_code,
            'reference_id' => $document->id,
            'source_type' => $document->type_document_code,
            'source_id' => $document->id,
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
                    $newStock = (float) $stock->stock_reel + $quantity;
                    if ($newStock < 0) {
                        throw new InsufficientStockException("Invalid cancellation causing negative stock for article {$line->article_id} in depot {$depot->id}.");
                    }
                    $stock->update(['stock_reel' => $newStock]);
                }

                // Create audit trail
                StockMovement::create([
                    'article_id' => $line->article_id,
                    'depot_id' => $depot->id,
                    'movement_type' => 'ADJUSTMENT',
                    'type' => 'adjustment',
                    'quantity' => abs($quantity),
                    'reference' => $document->do_piece,
                    'reference_type' => $document->type_document_code,
                    'reference_id' => $document->id,
                    'source_type' => $document->type_document_code,
                    'source_id' => $document->id,
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
        if ($newQuantity < 0) {
            throw new InsufficientStockException('Stock adjustment cannot set a negative quantity.');
        }

        $oldQuantity = (float) $stock->stock_reel;
        $difference = $newQuantity - $oldQuantity;

        DB::transaction(function () use ($stock, $newQuantity, $difference, $reason) {
            $stock->update(['stock_reel' => $newQuantity]);

            StockMovement::create([
                'article_id' => $stock->article_id,
                'depot_id' => $stock->depot_id,
                'movement_type' => 'ADJUSTMENT',
                'type' => 'adjustment',
                'quantity' => abs($difference),
                'source_type' => 'manual_adjustment',
                'source_id' => $stock->id,
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
        $movement = DocumentTypeRegistry::movementFromCode($typeCode);
        return $movement === 'NONE' ? null : $movement;
    }

    /**
     * Get direction multiplier: +1 for IN, -1 for OUT
     */
    private function getMovementDirection(string $movementType): int
    {
        return $movementType === 'IN' ? 1 : ($movementType === 'OUT' ? -1 : 0);
    }

    /**
     * Map internal movement type to database enum
     */
    private function mapMovementType(string $movementType): string
    {
        return in_array($movementType, ['IN', 'OUT', 'ADJUSTMENT', 'RETURN'], true)
            ? $movementType
            : 'ADJUSTMENT';
    }

    /**
     * Get default depot (first one or configured)
     */
    private function getDefaultDepot(): ?Depot
    {
        return Depot::orderBy('id')->first();
    }
}
