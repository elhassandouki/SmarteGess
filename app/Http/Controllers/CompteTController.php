<?php

namespace App\Http\Controllers;

use App\Models\CompteT;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompteTController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->string('search'));
        $type = trim((string) $request->string('type'));
        $entity = trim((string) $request->string('entity'));

        $query = CompteT::withCount(['documents', 'reglements'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('ct_num', 'like', '%'.$search.'%')
                        ->orWhere('code_tiers', 'like', '%'.$search.'%')
                        ->orWhere('ct_intitule', 'like', '%'.$search.'%')
                        ->orWhere('ct_telephone', 'like', '%'.$search.'%')
                        ->orWhere('ct_ice', 'like', '%'.$search.'%');
                });
            })
            ->when($type !== '', fn ($query) => $query->where('ct_type', $type))
            ->orderBy('ct_intitule');

        if ($request->ajax() && $request->has('draw')) {
            $draw = (int) $request->input('draw', 1);
            $start = max(0, (int) $request->input('start', 0));
            $length = max(10, (int) $request->input('length', 10));
            $recordsTotal = (clone $query)->count();
            $recordsFiltered = $recordsTotal;
            $rows = $query->skip($start)->take($length)->get();

            $data = $rows->map(function (CompteT $tier) {
                return [
                    'numero' => e($tier->ct_num),
                    'code' => e($tier->code_tiers ?: '-'),
                    'intitule' => '<div class="font-weight-bold">'.e($tier->ct_intitule).'</div><small class="text-muted">'.e($tier->ct_adresse ?: 'Adresse non renseignee').'</small>',
                    'type' => e(ucfirst($tier->ct_type)),
                    'telephone' => e($tier->ct_telephone ?: '-'),
                    'ice' => e($tier->ct_ice ?: '-'),
                    'delai' => (int) $tier->ct_delai_paiement.' j',
                    'documents' => (int) $tier->documents_count,
                    'actions' => '<div class="btn-group btn-group-sm" role="group">'
                        .'<a href="'.route('tiers.show', $tier).'" class="btn btn-xs btn-outline-secondary mr-2"><i class="fas fa-eye"></i></a>'
                        .'<a href="'.route('tiers.edit', $tier).'" class="btn btn-xs btn-outline-primary mr-2"><i class="fas fa-pen"></i></a>'
                        .'<form action="'.route('tiers.destroy', $tier).'" method="POST" data-ajax-delete="true" data-confirm="Supprimer ce tiers ?">'.csrf_field().method_field('DELETE').'<button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form>'
                        .'</div>',
                ];
            })->all();

            return response()->json(compact('draw', 'recordsTotal', 'recordsFiltered', 'data'));
        }

        $tiers = $query->get();

        return view('tiers.index', [
            'tiers' => $tiers,
            'entity' => $entity,
            'filters' => [
                'search' => $search,
                'type' => $type,
            ],
        ]);
    }

    public function clients(Request $request): View
    {
        $request->merge(['type' => 'client', 'entity' => 'clients']);
        return $this->index($request);
    }

    public function suppliers(Request $request): View
    {
        $request->merge(['type' => 'fournisseur', 'entity' => 'suppliers']);
        return $this->index($request);
    }

    public function create(): View
    {
        return view('tiers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateTier($request);

        $tier = CompteT::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tiers cree avec succes.',
                'tier' => $tier,
            ]);
        }

        return redirect()->route('tiers.index')->with('success', 'Tiers cree avec succes.');
    }

    public function edit(CompteT $tier): View
    {
        return view('tiers.edit', compact('tier'));
    }

    public function update(Request $request, CompteT $tier): RedirectResponse
    {
        $data = $this->validateTier($request, $tier->id);

        $tier->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tiers mis a jour avec succes.',
                'tier' => $tier->fresh(),
            ]);
        }

        return redirect()->route('tiers.index')->with('success', 'Tiers mis a jour avec succes.');
    }

    public function destroy(CompteT $tier): RedirectResponse
    {
        if ($tier->documents()->exists() || $tier->reglements()->exists()) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Impossible de supprimer un tiers lie a des documents ou reglements.',
                ], 422);
            }

            return redirect()->route('tiers.index')->with('error', 'Impossible de supprimer un tiers lie a des documents ou reglements.');
        }

        $tier->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Tiers supprime avec succes.',
            ]);
        }

        return redirect()->route('tiers.index')->with('success', 'Tiers supprime avec succes.');
    }

    public function show(CompteT $tier): View
    {
        return view('tiers.show', $this->buildTierInsights($tier));
    }

    protected function buildTierInsights(CompteT $tier): array
    {
        $tier->loadCount(['documents', 'reglements']);

        $salesTotal = (float) $tier->documents()->where('doc_module', 'sales')->sum('do_total_ttc');
        $purchaseTotal = (float) $tier->documents()->where('doc_module', 'purchase')->sum('do_total_ttc');
        $paidOnDocs = (float) $tier->documents()->sum('do_montant_regle');
        $reglementsTotal = (float) $tier->reglements()->sum('rg_montant');
        $encours = max($salesTotal - $paidOnDocs, 0);

        $lastSaleDate = $tier->documents()->where('doc_module', 'sales')->max('do_date');
        $lastPurchaseDate = $tier->documents()->where('doc_module', 'purchase')->max('do_date');
        $avgTicket = $tier->documents()->where('doc_module', 'sales')->avg('do_total_ttc') ?? 0;

        $recentDocuments = $tier->documents()->with('transporteur')->latest('do_date')->take(15)->get();
        $recentReglements = $tier->reglements()->with('document')->latest('rg_date')->take(15)->get();

        $topArticles = DB::table('f_docligne')
            ->join('f_docentete', 'f_docentete.id', '=', 'f_docligne.doc_id')
            ->join('f_articles', 'f_articles.id', '=', 'f_docligne.article_id')
            ->where('f_docentete.tier_id', $tier->id)
            ->selectRaw('f_articles.ar_design as name, SUM(f_docligne.dl_qte) as qty, SUM(f_docligne.dl_montant_ttc) as amount')
            ->groupBy('f_articles.id', 'f_articles.ar_design')
            ->orderByDesc('amount')
            ->limit(8)
            ->get();

        $from = now()->startOfMonth()->subMonths(5);
        $monthlyRows = $tier->documents()
            ->selectRaw("DATE_FORMAT(do_date, '%Y-%m') as ym")
            ->selectRaw('SUM(CASE WHEN doc_module = "sales" THEN do_total_ttc ELSE 0 END) as sales_total')
            ->selectRaw('SUM(CASE WHEN doc_module = "purchase" THEN do_total_ttc ELSE 0 END) as purchases_total')
            ->groupBy('ym')
            ->orderBy('ym')
            ->whereBetween('do_date', [$from->toDateString(), now()->endOfMonth()->toDateString()])
            ->get()
            ->keyBy('ym');

        $paymentRows = $tier->reglements()
            ->selectRaw("DATE_FORMAT(rg_date, '%Y-%m') as ym")
            ->selectRaw('SUM(rg_montant) as total')
            ->whereBetween('rg_date', [$from->toDateString(), now()->endOfMonth()->toDateString()])
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->keyBy('ym');

        $months = collect(range(0, 5))->map(fn (int $i) => $from->copy()->addMonths($i));
        $labels = $months->map(fn (Carbon $d) => $d->format('M Y'))->all();
        $salesSeries = $months->map(fn (Carbon $d) => (float) ($monthlyRows[$d->format('Y-m')]->sales_total ?? 0))->all();
        $purchaseSeries = $months->map(fn (Carbon $d) => (float) ($monthlyRows[$d->format('Y-m')]->purchases_total ?? 0))->all();
        $paymentSeries = $months->map(fn (Carbon $d) => (float) ($paymentRows[$d->format('Y-m')]->total ?? 0))->all();

        return [
            'tier' => $tier,
            'salesTotal' => $salesTotal,
            'purchaseTotal' => $purchaseTotal,
            'paidOnDocs' => $paidOnDocs,
            'reglementsTotal' => $reglementsTotal,
            'encours' => $encours,
            'lastSaleDate' => $lastSaleDate,
            'lastPurchaseDate' => $lastPurchaseDate,
            'avgTicket' => (float) $avgTicket,
            'recentDocuments' => $recentDocuments,
            'recentReglements' => $recentReglements,
            'topArticles' => $topArticles,
            'chartData' => [
                'labels' => $labels,
                'sales' => $salesSeries,
                'purchases' => $purchaseSeries,
                'payments' => $paymentSeries,
            ],
        ];
    }

    protected function validateTier(Request $request, ?int $tierId = null): array
    {
        $data = $request->validate([
            'ct_num' => ['required', 'string', 'max:100', Rule::unique('f_comptet', 'ct_num')->ignore($tierId)],
            'code_tiers' => ['nullable', 'string', 'max:100', Rule::unique('f_comptet', 'code_tiers')->ignore($tierId)],
            'ct_intitule' => ['required', 'string', 'max:255'],
            'ct_type' => ['required', Rule::in(['client', 'fournisseur', 'prospect'])],
            'ct_ice' => ['nullable', 'string', 'max:15', Rule::unique('f_comptet', 'ct_ice')->ignore($tierId)],
            'ct_if' => ['nullable', 'string', 'max:20'],
            'ct_encours_max' => ['nullable', 'numeric', 'min:0'],
            'ct_delai_paiement' => ['nullable', 'integer', 'min:0'],
            'ct_telephone' => ['nullable', 'string', 'max:50'],
            'ct_adresse' => ['nullable', 'string', 'max:255'],
        ]);

        $data['code_tiers'] = $data['code_tiers'] ?? $data['ct_num'];
        $data['ct_encours_max'] = $data['ct_encours_max'] ?? 0;
        $data['ct_delai_paiement'] = $data['ct_delai_paiement'] ?? 0;
        $data['entity_type'] = match ($data['ct_type']) {
            'fournisseur' => 'supplier',
            'prospect' => 'prospect',
            default => 'client',
        };

        return $data;
    }
}
