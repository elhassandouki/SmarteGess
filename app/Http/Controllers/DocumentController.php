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
use App\Services\ERP\DocumentWorkflowService;
use App\Services\ERP\AccountingPostingService;
use App\Services\StockMovementService;
use App\Support\DocumentTypeRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DocumentController extends Controller
{
    protected StockMovementService $stockMovementService;
    protected DocumentWorkflowService $documentWorkflowService;
    protected AccountingPostingService $accountingPostingService;

    public function __construct(
        StockMovementService $stockMovementService,
        DocumentWorkflowService $documentWorkflowService,
        AccountingPostingService $accountingPostingService
    )
    {
        $this->middleware('auth');
        $this->stockMovementService = $stockMovementService;
        $this->documentWorkflowService = $documentWorkflowService;
        $this->accountingPostingService = $accountingPostingService;
    }

    public function index(Request $request): View|JsonResponse
    {
        $module = (string) $request->string('module')->value() ?: null;
        $dateFrom = $request->date('date_from');
        $dateTo = $request->date('date_to');
        $typeCode = trim((string) $request->string('type_document_code'));
        $tierId = $request->integer('tier_id') ?: null;
        $paymentStatus = $request->string('payment_status')->value();

        $query = Document::query()
            ->with('transporteur')
            ->with('tier')
            ->withCount('lines')
            ->when($module, fn ($query) => $query->byModule($module))
            ->when($dateFrom, fn ($query) => $query->whereDate('do_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('do_date', '<=', $dateTo))
            ->when($typeCode !== '', fn ($query) => $query->where('type_document_code', $typeCode))
            ->when($tierId, fn ($query) => $query->where('tier_id', $tierId))
            ->when($paymentStatus !== null && $paymentStatus !== '', fn ($query) => $query->where('do_statut', (int) $paymentStatus));

        if ($request->ajax() && $request->has('draw')) {
            return $this->documentsDataTableResponse($request, $query);
        }

        $documents = $query
            ->latest('do_date')
            ->latest('id')
            ->paginate(100)
            ->withQueryString();

        return view('documents.index', [
            'documents' => $documents,
            'types' => $this->types(),
            'module' => $module,
            'statusMap' => $this->paymentStatuses(),
            'tiers' => CompteT::orderBy('ct_intitule')->get(),
            'filters' => [
                'date_from' => $dateFrom?->format('Y-m-d'),
                'date_to' => $dateTo?->format('Y-m-d'),
                'type_document_code' => $typeCode,
                'tier_id' => $tierId,
                'payment_status' => $paymentStatus,
                'module' => $module,
            ],
        ]);
    }

    protected function documentsDataTableResponse(Request $request, \Illuminate\Database\Eloquent\Builder $query): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = max(10, (int) $request->input('length', 10));
        $searchValue = trim((string) data_get($request->input('search'), 'value', ''));
        $orderColumnIndex = (int) data_get($request->input('order'), '0.column', 1);
        $orderDirection = strtolower((string) data_get($request->input('order'), '0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $columnsMap = [
            0 => 'do_piece',
            1 => 'do_date',
            2 => 'type_document_code',
            3 => 'tier_id',
            4 => 'transporteur_id',
            5 => 'do_expedition_statut',
            6 => 'do_statut',
            7 => 'id',
            8 => 'do_total_ht',
        ];

        $recordsTotal = (clone $query)->count();

        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $q->where('do_piece', 'like', "%{$searchValue}%")
                    ->orWhereHas('tier', function ($tierQuery) use ($searchValue) {
                        $tierQuery->where('ct_num', 'like', "%{$searchValue}%")
                            ->orWhere('code_tiers', 'like', "%{$searchValue}%")
                            ->orWhere('ct_intitule', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('transporteur', function ($transporteurQuery) use ($searchValue) {
                        $transporteurQuery->where('tr_nom', 'like', "%{$searchValue}%");
                    });
            });
        }

        $recordsFiltered = (clone $query)->count();

        $orderColumn = $columnsMap[$orderColumnIndex] ?? 'do_date';
        $documents = $query
            ->orderBy($orderColumn, $orderDirection)
            ->orderBy('id', 'desc')
            ->skip($start)
            ->take($length)
            ->get();

        $types = $this->types();
        $statusMap = $this->paymentStatuses();
        $statusThemes = [
            'livre' => 'success',
            'en_cours' => 'info',
            'en_attente' => 'secondary',
        ];

        $data = $documents->map(function (Document $document) use ($types, $statusMap, $statusThemes) {
            $piece = e($document->do_piece);
            $date = optional($document->do_date)->format('d/m/Y') ?: '-';
            $type = e($types[$document->type_document_code ?: 'BC'] ?? 'N/A');
            $tier = e($document->tier?->code_tiers ?: $document->tier?->ct_num ?: '-');
            $transporteur = e($document->transporteur?->tr_nom ?? '-');
            $logisticStatus = ucfirst(str_replace('_', ' ', $document->do_expedition_statut));
            $paymentStatus = $statusMap[$document->do_statut] ?? 'N/A';
            $linesCount = (int) $document->lines_count;
            $totalHt = number_format((float) $document->do_total_ht, 2).' DH';

            $actionHtml = '<div class="btn-group btn-group-sm" role="group">'
                .'<a href="'.route('documents.show', $document).'" class="btn btn-xs btn-outline-secondary mr-2"><i class="fas fa-eye"></i></a>'
                .'<button type="button" class="btn btn-xs btn-outline-dark mr-2 quick-view-btn" data-toggle="modal" data-target="#quickViewModal" data-piece="'.e($document->do_piece).'" data-date="'.optional($document->do_date)->format('Y-m-d').'" data-type="'.$type.'" data-tier="'.$tier.'" data-total="'.number_format((float) $document->do_total_ttc, 2).'" data-lines="'.$linesCount.'"><i class="fas fa-search-plus"></i></button>'
                .'<a href="'.route('documents.edit', $document).'" class="btn btn-xs btn-outline-primary mr-2"><i class="fas fa-pen"></i></a>'
                .'<form action="'.route('documents.duplicate', $document).'" method="POST" class="mr-2">'.csrf_field().'<button type="submit" class="btn btn-xs btn-outline-warning" title="Dupliquer"><i class="fas fa-copy"></i></button></form>'
                .'<button type="button" class="btn btn-xs btn-outline-success mr-2 quick-status-btn" data-toggle="modal" data-target="#quickStatusModal" data-action="'.route('documents.update-status', $document).'" data-current="'.e((string) $document->do_expedition_statut).'"><i class="fas fa-shipping-fast"></i></button>'
                .'<a href="'.route('reglements.create', ['doc_id' => $document->id, 'tier_id' => $document->tier_id]).'" class="btn btn-xs btn-outline-dark mr-2" title="Reglement"><i class="fas fa-cash-register"></i></a>'
                .'<form action="'.route('documents.destroy', $document).'" method="POST" data-ajax-delete="true" data-confirm="Supprimer ce document ?">'.csrf_field().method_field('DELETE').'<button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form>'
                .'</div>';

            return [
                'piece' => $piece,
                'date' => '<div class="text-center">'.$date.'</div>',
                'type' => $type,
                'tier' => $tier,
                'transporteur' => $transporteur,
                'statut' => '<div class="text-center"><span class="badge bg-'.($statusThemes[$document->do_expedition_statut] ?? 'secondary').'">'.e($logisticStatus).'</span></div>',
                'paiement' => '<div class="text-center"><span class="badge bg-'.($document->do_statut == 2 ? 'success' : ($document->do_statut == 1 ? 'warning' : 'secondary')).'">'.e($paymentStatus).'</span></div>',
                'lignes' => '<div class="text-center">'.$linesCount.'</div>',
                'total_ht' => '<div class="text-right">'.$totalHt.'</div>',
                'actions' => $actionHtml,
            ];
        })->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function create(): View
    {
        $module = request()->string('module')->value();
        return view('documents.create', $this->formData($module !== '' ? $module : null));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->normalizeTypeCode($request);
        $data = $this->validateDocument($request);

        DB::transaction(function () use ($data) {
            $document = Document::create($this->documentWorkflowService->buildHeaderData($data));
            $this->documentWorkflowService->syncLines($document, $data['lines']);
            // Process stock movements
            $this->stockMovementService->processDocumentMovement($document);
            $this->accountingPostingService->syncDocumentPosting($document);
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
            $this->formData($document->module),
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
            
            $document->update($this->documentWorkflowService->buildHeaderData($data));
            $document->lines()->delete();
            $this->documentWorkflowService->syncLines($document, $data['lines']);
            
            // Process stock movements (reverse old, create new)
            $this->stockMovementService->processDocumentMovement($document, $oldLines);
            $this->accountingPostingService->syncDocumentPosting($document);
        });

        return redirect()->route('documents.index')->with('success', 'Document mis a jour avec succes.');
    }

    public function destroy(Request $request, Document $document): RedirectResponse|JsonResponse
    {
        DB::transaction(function () use ($document) {
            // Reverse stock movements before deleting
            $this->stockMovementService->deleteDocumentMovements($document);
            $this->accountingPostingService->clearDocumentPosting($document->id);
            $document->lines()->delete();
            $document->delete();
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Document supprime avec succes.',
            ]);
        }

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

    public function updateStatus(Request $request, Document $document): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'do_expedition_statut' => ['required', Rule::in(array_keys($this->statuts()))],
        ]);

        $document->update([
            'do_expedition_statut' => $data['do_expedition_statut'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Statut du document mis a jour avec succes.',
            ]);
        }

        return redirect()
            ->route('documents.index')
            ->with('success', 'Statut du document mis a jour avec succes.');
    }

    protected function formData(?string $module = null): array
    {
        $types = $module ? DocumentTypeRegistry::codesByModule($module) : $this->types();

        return [
            'articles' => Article::orderBy('ar_design')->get(),
            'tiers' => CompteT::orderBy('ct_intitule')->get(),
            'depots' => Depot::orderBy('intitule')->get(),
            'transporteurs' => Transporteur::orderBy('tr_nom')->get(),
            'types' => $types,
            'statuts' => $this->statuts(),
            'module' => $module,
        ];
    }

    protected function types(): array
    {
        return DocumentTypeRegistry::labels();
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
        $module = (string) $request->input('module', '');
        $allowedForModule = $module !== ''
            ? array_keys(DocumentTypeRegistry::codesByModule($module))
            : $types;

        return $request->validate([
            'do_piece' => ['required', 'string', 'max:100', Rule::unique('f_docentete', 'do_piece')->ignore($documentId)],
            'do_date' => ['required', 'date'],
            'tier_id' => ['nullable', 'exists:f_comptet,id'],
            'depot_id' => ['nullable', 'exists:f_depots,id'],
            'module' => ['nullable', Rule::in([
                DocumentTypeRegistry::MODULE_SALES,
                DocumentTypeRegistry::MODULE_PURCHASE,
                DocumentTypeRegistry::MODULE_STOCK,
            ])],
            'type_document_code' => ['required', Rule::in($allowedForModule)],
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
