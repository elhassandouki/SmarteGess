<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Models\Article;
use App\Models\CompteT;
use App\Models\Depot;
use App\Models\Document;
use App\Models\DocumentLine;
use App\Models\Transporteur;
use App\Services\StockMovementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DocumentController extends Controller
{
    protected StockMovementService $stockMovementService;

    public function __construct(StockMovementService $stockMovementService)
    {
        $this->middleware('auth');
        $this->stockMovementService = $stockMovementService;
    }

    public function index(Request $request): View
    {
        $dateFrom = $request->date('date_from');
        $dateTo = $request->date('date_to');
        $typeCode = trim((string) $request->string('type_document_code'));
        $tierId = $request->integer('tier_id') ?: null;
        $paymentStatus = $request->string('payment_status')->value();

        $documents = Document::with('transporteur')
            ->with('tier')
            ->withCount('lines')
            ->when($dateFrom, fn ($query) => $query->whereDate('do_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('do_date', '<=', $dateTo))
            ->when($typeCode !== '', fn ($query) => $query->where('type_document_code', $typeCode))
            ->when($tierId, fn ($query) => $query->where('tier_id', $tierId))
            ->when($paymentStatus !== null && $paymentStatus !== '', fn ($query) => $query->where('do_statut', (int) $paymentStatus))
            ->latest('do_date')
            ->latest('id')
            ->get();

        return view('documents.index', [
            'documents' => $documents,
            'types' => $this->types(),
            'statusMap' => $this->paymentStatuses(),
            'tiers' => CompteT::orderBy('ct_intitule')->get(),
            'filters' => [
                'date_from' => $dateFrom?->format('Y-m-d'),
                'date_to' => $dateTo?->format('Y-m-d'),
                'type_document_code' => $typeCode,
                'tier_id' => $tierId,
                'payment_status' => $paymentStatus,
            ],
        ]);
    }

    public function create(): View
    {
        return view('documents.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->normalizeTypeCode($request);
        $data = $this->validateDocument($request);

        DB::transaction(function () use ($data) {
            $document = Document::create($this->extractDocumentData($data));
            $this->syncLines($document, $data['lines']);
            // Process stock movements
            $this->stockMovementService->processDocumentMovement($document);
        });

        return redirect()->route('documents.index')->with('success', 'Document cree avec succes.');
    }

    public function show(Document $document): View
    {
        $document->load([
            'tier',
            'depot',
            'transporteur',
            'lines.article',
            'reglements',
        ]);

        // Load stock movements for this document
        $stockMovements = \App\Models\StockMovement::where('reference_id', $document->id)
            ->where('reference_type', $document->type_document_code)
            ->with('article', 'depot')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('documents.show', [
            'document' => $document,
            'stockMovements' => $stockMovements,
            'types' => $this->types(),
        ]);
    }

    public function edit(Document $document): View
    {
        $document->load('lines');

        return view('documents.edit', array_merge(
            $this->formData(),
            ['document' => $document]
        ));
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        $this->normalizeTypeCode($request);
        $data = $this->validateDocument($request, $document->id);

        DB::transaction(function () use ($document, $data) {
            // Get old lines before deletion for stock reversal
            $oldLines = $document->lines->map(fn ($line) => [
                'article_id' => $line->article_id,
                'dl_qte' => $line->dl_qte,
            ])->toArray();
            
            $document->update($this->extractDocumentData($data));
            $document->lines()->delete();
            $this->syncLines($document, $data['lines']);
            
            // Process stock movements (reverse old, create new)
            $this->stockMovementService->processDocumentMovement($document, $oldLines);
        });

        return redirect()->route('documents.index')->with('success', 'Document mis a jour avec succes.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        DB::transaction(function () use ($document) {
            // Reverse stock movements before deleting
            $this->stockMovementService->deleteDocumentMovements($document);
            $document->lines()->delete();
            $document->delete();
        });

        return redirect()->route('documents.index')->with('success', 'Document supprime avec succes.');
    }

    public function duplicate(Document $document): RedirectResponse
    {
        $document->load('lines');

        $newDocument = DB::transaction(function () use ($document) {
            $copy = Document::create([
                'do_piece' => $this->generateDuplicatePiece($document->do_piece),
                'do_date' => now()->toDateString(),
                'tier_id' => $document->tier_id,
                'do_type' => $document->do_type,
                'type_document_code' => $document->type_document_code,
                'depot_id' => $document->depot_id,
                'transporteur_id' => $document->transporteur_id,
                'do_lieu_livraison' => $document->do_lieu_livraison,
                'do_date_livraison' => $document->do_date_livraison,
                'do_expedition_statut' => 'en_attente',
                'do_total_ht' => $document->do_total_ht,
                'do_total_tva' => $document->do_total_tva,
                'do_total_ttc' => $document->do_total_ttc,
                'do_montant_regle' => 0,
                'do_statut' => 0,
            ]);

            $document->lines->each(function (DocumentLine $line) use ($copy) {
                $copy->lines()->create([
                    'article_id' => $line->article_id,
                    'dl_qte' => $line->dl_qte,
                    'dl_prix_unitaire_ht' => $line->dl_prix_unitaire_ht,
                    'dl_prix_revient' => $line->dl_prix_revient,
                    'dl_remise_percent' => $line->dl_remise_percent,
                    'dl_montant_ht' => $line->dl_montant_ht,
                    'dl_montant_ttc' => $line->dl_montant_ttc,
                ]);
            });
            
            // Process stock movements for the duplicate
            $this->stockMovementService->processDocumentMovement($copy);

            return $copy;
        });

        return redirect()
            ->route('documents.edit', $newDocument)
            ->with('success', 'Document duplique avec succes. Vous pouvez maintenant l ajuster.');
    }

    public function updateStatus(Request $request, Document $document): RedirectResponse
    {
        $data = $request->validate([
            'do_expedition_statut' => ['required', Rule::in(array_keys($this->statuts()))],
        ]);

        $document->update([
            'do_expedition_statut' => $data['do_expedition_statut'],
        ]);

        return redirect()
            ->route('documents.index')
            ->with('success', 'Statut du document mis a jour avec succes.');
    }

    protected function formData(): array
    {
        return [
            'articles' => Article::orderBy('ar_design')->get(),
            'tiers' => CompteT::orderBy('ct_intitule')->get(),
            'depots' => Depot::orderBy('intitule')->get(),
            'transporteurs' => Transporteur::orderBy('tr_nom')->get(),
            'types' => $this->types(),
            'statuts' => $this->statuts(),
        ];
    }

    protected function types(): array
    {
        return [
            'DE' => 'Devis',
            'BC' => 'Bon de commande',
            'BL' => 'Bon de livraison',
            'FA' => 'Facture',
            'BR' => 'Bon de retour',
            'FR' => 'Facture retour',
        ];
    }

    protected function numericTypeFromCode(string $code): int
    {
        return match ($code) {
            'BL' => 2,
            'FA' => 3,
            'BR', 'FR' => 4,
            default => 1,
        };
    }

    protected function statuts(): array
    {
        return [
            'en_attente' => 'En attente',
            'en_cours' => 'En cours',
            'livre' => 'Livre',
        ];
    }

    protected function normalizeTypeCode(Request $request): void
    {
        if ($request->filled('type_document_code')) {
            return;
        }

        $legacyType = $request->input('do_type');

        if ($legacyType === null || $legacyType === '') {
            return;
        }

        $request->merge([
            'type_document_code' => $this->typeCodeFromLegacyValue((int) $legacyType),
        ]);
    }

    protected function typeCodeFromLegacyValue(int $legacyType): string
    {
        return match ($legacyType) {
            2 => 'BL',
            3 => 'FA',
            4 => 'BR',
            default => 'BC',
        };
    }

    protected function validateDocument(Request $request, ?int $documentId = null): array
    {
        $types = array_keys($this->types());

        return $request->validate([
            'do_piece' => ['required', 'string', 'max:100', Rule::unique('f_docentete', 'do_piece')->ignore($documentId)],
            'do_date' => ['required', 'date'],
            'tier_id' => ['nullable', 'exists:f_comptet,id'],
            'depot_id' => ['nullable', 'exists:f_depots,id'],
            'type_document_code' => ['required', Rule::in($types)],
            'transporteur_id' => ['nullable', 'exists:f_transporteurs,id'],
            'do_lieu_livraison' => ['nullable', 'string', 'max:255'],
            'do_date_livraison' => ['nullable', 'date'],
            'do_expedition_statut' => ['required', Rule::in(array_keys($this->statuts()))],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.article_id' => ['required', 'exists:f_articles,id'],
            'lines.*.dl_qte' => ['required', 'numeric', 'gt:0'],
            'lines.*.dl_prix_unitaire_ht' => ['required', 'numeric', 'min:0'],
            'lines.*.dl_remise_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
    }

    protected function extractDocumentData(array $data): array
    {
        $taxRates = $this->articleTaxRates(collect($data['lines'])->pluck('article_id')->all());

        $totals = collect($data['lines'])->reduce(function (array $carry, array $line) use ($taxRates) {
            $lineTotalHt = $this->lineTotalHt($line);
            $taxRate = (float) ($taxRates[(int) $line['article_id']] ?? 0);
            $lineTotalTva = $lineTotalHt * ($taxRate / 100);

            $carry['ht'] += $lineTotalHt;
            $carry['tva'] += $lineTotalTva;
            $carry['ttc'] += $lineTotalHt + $lineTotalTva;

            return $carry;
        }, ['ht' => 0, 'tva' => 0, 'ttc' => 0]);

        return [
            'do_piece' => $data['do_piece'],
            'do_date' => $data['do_date'],
            'tier_id' => $data['tier_id'] ?? null,
            'depot_id' => $data['depot_id'] ?? null,
            'do_type' => $this->numericTypeFromCode($data['type_document_code']),
            'type_document_code' => $data['type_document_code'],
            'transporteur_id' => $data['transporteur_id'] ?? null,
            'do_lieu_livraison' => $data['do_lieu_livraison'] ?? null,
            'do_date_livraison' => $data['do_date_livraison'] ?? null,
            'do_expedition_statut' => $data['do_expedition_statut'],
            'do_total_ht' => $totals['ht'],
            'do_total_tva' => $totals['tva'],
            'do_total_ttc' => $totals['ttc'],
            'do_montant_regle' => 0,
        ];
    }

    protected function syncLines(Document $document, array $lines): void
    {
        $taxRates = $this->articleTaxRates(collect($lines)->pluck('article_id')->all());

        foreach ($lines as $line) {
            $quantity = (float) $line['dl_qte'];
            $price = (float) $line['dl_prix_unitaire_ht'];
            $discount = $this->lineDiscount($line);
            $lineTotalHt = $this->lineTotalHt($line);
            $taxRate = (float) ($taxRates[(int) $line['article_id']] ?? 0);
            $lineTotalTtc = $lineTotalHt + ($lineTotalHt * ($taxRate / 100));

            $document->lines()->create([
                'article_id' => $line['article_id'],
                'dl_qte' => $quantity,
                'dl_prix_unitaire_ht' => $price,
                'dl_remise_percent' => $discount,
                'dl_montant_ht' => $lineTotalHt,
                'dl_montant_ttc' => $lineTotalTtc,
            ]);
        }
    }

    protected function lineDiscount(array $line): float
    {
        return max(0, min(100, (float) ($line['dl_remise_percent'] ?? 0)));
    }

    protected function lineTotalHt(array $line): float
    {
        $grossTotal = (float) $line['dl_qte'] * (float) $line['dl_prix_unitaire_ht'];
        $discount = $this->lineDiscount($line);

        return $grossTotal - ($grossTotal * ($discount / 100));
    }

    protected function articleTaxRates(array $articleIds): array
    {
        return Article::query()
            ->whereIn('id', array_filter(array_map('intval', $articleIds)))
            ->pluck('ar_tva', 'id')
            ->map(fn ($value) => (float) $value)
            ->all();
    }

    protected function generateDuplicatePiece(string $piece): string
    {
        $basePiece = Str::limit($piece, 80, '');
        $candidate = $basePiece.'-COPIE';
        $suffix = 2;

        while (Document::where('do_piece', $candidate)->exists()) {
            $candidate = $basePiece.'-COPIE-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    protected function paymentStatuses(): array
    {
        return [
            0 => 'Non regle',
            1 => 'Partiellement regle',
            2 => 'Regle',
        ];
    }
}
