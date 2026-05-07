<?php

namespace App\Http\Controllers;

use App\Models\CompteT;
use App\Models\Document;
use App\Models\Reglement;
use App\Services\ERP\PaymentWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReglementController extends Controller
{
    public function __construct(protected PaymentWorkflowService $paymentWorkflowService)
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $dateFrom = $request->date('date_from');
        $dateTo = $request->date('date_to');
        $tierId = $request->integer('tier_id') ?: null;
        $mode = $request->integer('mode') ?: null;
        $validated = $request->string('validated')->value();

        $reglements = Reglement::with(['tier', 'document'])
            ->when($dateFrom, fn ($query) => $query->whereDate('rg_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('rg_date', '<=', $dateTo))
            ->when($tierId, fn ($query) => $query->where('tier_id', $tierId))
            ->when($mode, fn ($query) => $query->where('rg_mode_reglement', $mode))
            ->when($validated !== null && $validated !== '', fn ($query) => $query->where('rg_valide', $validated === '1'))
            ->latest('rg_date')
            ->latest('id')
            ->get();

        return view('reglements.index', [
            'reglements' => $reglements,
            'tiers' => CompteT::orderBy('ct_intitule')->get(),
            'modes' => $this->modes(),
            'filters' => [
                'date_from' => $dateFrom?->format('Y-m-d'),
                'date_to' => $dateTo?->format('Y-m-d'),
                'tier_id' => $tierId,
                'mode' => $mode,
                'validated' => $validated,
            ],
        ]);
    }

    public function create(): View
    {
        return view('reglements.create', [
            'tiers' => CompteT::orderBy('ct_intitule')->get(),
            'documents' => Document::orderByDesc('do_date')->get(),
            'modes' => $this->modes(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'doc_id' => ['nullable', 'exists:f_docentete,id'],
            'tier_id' => ['required', 'exists:f_comptet,id'],
            'rg_date' => ['required', 'date'],
            'rg_libelle' => ['nullable', 'string', 'max:255'],
            'rg_montant' => ['required', 'numeric', 'gt:0'],
            'rg_mode_reglement' => ['required', Rule::in(array_keys($this->modes()))],
            'rg_reference' => ['nullable', 'string', 'max:255'],
            'rg_date_echeance' => ['nullable', 'date'],
            'rg_banque' => ['nullable', 'string', 'max:255'],
            'rg_valide' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $data) {
            $reglement = Reglement::create([
                ...$data,
                'rg_valide' => $request->boolean('rg_valide'),
            ]);

            $this->paymentWorkflowService->syncDocumentPayment($reglement->document);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Reglement ajoute avec succes.',
            ]);
        }

        return redirect()->route('reglements.index')->with('success', 'Reglement ajoute avec succes.');
    }

    public function destroy(Request $request, Reglement $reglement): RedirectResponse|JsonResponse
    {
        DB::transaction(function () use ($reglement) {
            $document = $reglement->document;
            $reglement->delete();
            $this->paymentWorkflowService->syncDocumentPayment($document);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Reglement supprime avec succes.',
            ]);
        }

        return redirect()->route('reglements.index')->with('success', 'Reglement supprime avec succes.');
    }

    protected function modes(): array
    {
        return [
            1 => 'Especes',
            2 => 'Cheque',
            3 => 'Virement',
            4 => 'Effet / Traite',
        ];
    }
}
