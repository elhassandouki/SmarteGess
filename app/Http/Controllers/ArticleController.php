<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Family;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->string('search'));
        $familyId = $request->integer('family_id') ?: null;
        $lowOnly = $request->boolean('low_only');

        $query = Article::with('family')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('code_article', 'like', '%'.$search.'%')
                        ->orWhere('ar_ref', 'like', '%'.$search.'%')
                        ->orWhere('ar_design', 'like', '%'.$search.'%')
                        ->orWhere('ar_code_barre', 'like', '%'.$search.'%');
                });
            })
            ->when($familyId, fn ($query) => $query->where('family_id', $familyId))
            ->when($lowOnly, fn ($query) => $query->whereColumn('ar_stock_actuel', '<=', 'ar_stock_min'));

        if ($request->ajax() && $request->has('draw')) {
            return $this->datatableResponse($request, $query);
        }

        $articles = $query->latest()->get();

        return view('articles.index', [
            'articles' => $articles,
            'families' => Family::orderBy('fa_intitule')->get(),
            'filters' => [
                'search' => $search,
                'family_id' => $familyId,
                'low_only' => $lowOnly,
            ],
        ]);
    }

    protected function datatableResponse(Request $request, $query): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = max(10, (int) $request->input('length', 10));
        $recordsTotal = (clone $query)->count();
        $recordsFiltered = $recordsTotal;

        $rows = $query->orderByDesc('id')->skip($start)->take($length)->get();
        $data = $rows->map(function (Article $article) {
            return [
                'code' => e($article->code_article ?: $article->ar_ref),
                'ref' => e($article->ar_ref),
                'designation' => e($article->ar_design),
                'famille' => e($article->family?->fa_intitule ?? '-'),
                'prix_achat' => number_format((float) $article->ar_prix_achat, 2),
                'prix_vente' => number_format((float) $article->ar_prix_vente, 2),
                'tva' => number_format((float) $article->ar_tva, 2).'%',
                'stock' => number_format((float) $article->ar_stock_actuel, 3),
                'stock_min' => number_format((float) $article->ar_stock_min, 3),
                'unite' => e($article->ar_unite),
                'actions' => '<div class="btn-group btn-group-sm" role="group">'
                    .'<a href="'.route('articles.show', $article).'" class="btn btn-xs btn-outline-secondary mr-2"><i class="fas fa-eye"></i></a>'
                    .'<a href="'.route('articles.edit', $article).'" class="btn btn-xs btn-outline-primary mr-2"><i class="fas fa-pen"></i></a>'
                    .'<form action="'.route('articles.destroy', $article).'" method="POST" onsubmit="return confirm(\'Supprimer cet article ?\');">'.csrf_field().method_field('DELETE').'<button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form>'
                    .'</div>',
            ];
        })->all();

        return response()->json(compact('draw', 'recordsTotal', 'recordsFiltered', 'data'));
    }

    public function create(): View
    {
        $families = Family::orderBy('fa_intitule')->get();

        return view('articles.create', compact('families'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateArticle($request);

        Article::create($data);

        return redirect()->route('articles.index')->with('success', 'Article cree avec succes.');
    }

    public function edit(Article $article): View
    {
        $families = Family::orderBy('fa_intitule')->get();

        return view('articles.edit', compact('article', 'families'));
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $data = $this->validateArticle($request, $article->id);

        $article->update($data);

        return redirect()->route('articles.index')->with('success', 'Article mis a jour avec succes.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        if ($article->documentLines()->exists()) {
            return redirect()->route('articles.index')->with('error', 'Impossible de supprimer un article deja utilise dans un document.');
        }

        $article->delete();

        return redirect()->route('articles.index')->with('success', 'Article supprime avec succes.');
    }

    public function show(Article $article): View
    {
        return view('articles.show', $this->buildArticleInsights($article));
    }

    public function exportPdf(Article $article)
    {
        $data = $this->buildArticleInsights($article);
        $data['generatedAt'] = now();

        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            throw new \RuntimeException('PDF engine not installed. Install barryvdh/laravel-dompdf in all environments.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('articles.export-pdf', $data);
        return $pdf->download('article-'.$article->id.'-report.pdf');
    }

    protected function buildArticleInsights(Article $article): array
    {
        $article->load('family', 'stocks.depot');

        $salesAgg = $article->documentLines()
            ->join('f_docentete', 'f_docentete.id', '=', 'f_docligne.doc_id')
            ->where('f_docentete.doc_module', 'sales')
            ->selectRaw('SUM(f_docligne.dl_qte) as qty, SUM(f_docligne.dl_montant_ttc) as amount, COUNT(DISTINCT f_docentete.id) as docs')
            ->first();

        $purchaseAgg = $article->documentLines()
            ->join('f_docentete', 'f_docentete.id', '=', 'f_docligne.doc_id')
            ->where('f_docentete.doc_module', 'purchase')
            ->selectRaw('SUM(f_docligne.dl_qte) as qty, SUM(f_docligne.dl_montant_ttc) as amount, COUNT(DISTINCT f_docentete.id) as docs')
            ->first();

        $lastSalesDate = $article->documentLines()->join('f_docentete', 'f_docentete.id', '=', 'f_docligne.doc_id')->where('f_docentete.doc_module', 'sales')->max('f_docentete.do_date');
        $lastPurchaseDate = $article->documentLines()->join('f_docentete', 'f_docentete.id', '=', 'f_docligne.doc_id')->where('f_docentete.doc_module', 'purchase')->max('f_docentete.do_date');

        $topClients = $article->documentLines()->join('f_docentete', 'f_docentete.id', '=', 'f_docligne.doc_id')->join('f_comptet', 'f_comptet.id', '=', 'f_docentete.tier_id')->where('f_docentete.doc_module', 'sales')->selectRaw('f_comptet.ct_intitule as name, SUM(f_docligne.dl_montant_ttc) as amount')->groupBy('f_comptet.id', 'f_comptet.ct_intitule')->orderByDesc('amount')->limit(5)->get();
        $topSuppliers = $article->documentLines()->join('f_docentete', 'f_docentete.id', '=', 'f_docligne.doc_id')->join('f_comptet', 'f_comptet.id', '=', 'f_docentete.tier_id')->where('f_docentete.doc_module', 'purchase')->selectRaw('f_comptet.ct_intitule as name, SUM(f_docligne.dl_montant_ttc) as amount')->groupBy('f_comptet.id', 'f_comptet.ct_intitule')->orderByDesc('amount')->limit(5)->get();

        $lastDocuments = $article->documentLines()->with(['document.tier'])->whereHas('document')->latest('id')->take(15)->get();
        $stockMovements = $article->stockMovements()->with(['depot', 'user'])->latest('created_at')->take(20)->get();

        $monthlyRows = $article->documentLines()->join('f_docentete', 'f_docentete.id', '=', 'f_docligne.doc_id')->whereDate('f_docentete.do_date', '>=', now()->subMonths(5)->startOfMonth())->selectRaw("DATE_FORMAT(f_docentete.do_date, '%Y-%m') as ym")->selectRaw('SUM(CASE WHEN f_docentete.doc_module = "sales" THEN f_docligne.dl_montant_ttc ELSE 0 END) as sales_amount')->selectRaw('SUM(CASE WHEN f_docentete.doc_module = "purchase" THEN f_docligne.dl_montant_ttc ELSE 0 END) as purchase_amount')->groupBy('ym')->orderBy('ym')->get()->keyBy('ym');
        $months = collect(range(0, 5))->map(fn (int $i) => now()->subMonths(5 - $i));
        $chartLabels = $months->map(fn (Carbon $d) => $d->format('M Y'))->all();
        $salesSeries = $months->map(fn (Carbon $d) => (float) ($monthlyRows[$d->format('Y-m')]->sales_amount ?? 0))->all();
        $purchaseSeries = $months->map(fn (Carbon $d) => (float) ($monthlyRows[$d->format('Y-m')]->purchase_amount ?? 0))->all();

        $salesAmount = (float) ($salesAgg?->amount ?? 0);
        $purchaseAmount = (float) ($purchaseAgg?->amount ?? 0);
        $salesQty = (float) ($salesAgg?->qty ?? 0);
        $purchaseQty = (float) ($purchaseAgg?->qty ?? 0);
        $avgSalePrice = $salesQty > 0 ? $salesAmount / $salesQty : 0;
        $stockValorisation = (float) $article->ar_stock_actuel * (float) $article->ar_prix_achat;
        $last90dSalesQty = (float) $article->documentLines()->join('f_docentete', 'f_docentete.id', '=', 'f_docligne.doc_id')->where('f_docentete.doc_module', 'sales')->whereDate('f_docentete.do_date', '>=', now()->subDays(90))->sum('f_docligne.dl_qte');
        $rotation90d = $last90dSalesQty > 0 ? ((float) $article->ar_stock_actuel / $last90dSalesQty) * 90 : null;

        return [
            'article' => $article,
            'salesQty' => $salesQty,
            'salesAmount' => $salesAmount,
            'purchaseQty' => $purchaseQty,
            'purchaseAmount' => $purchaseAmount,
            'margin' => $salesAmount - $purchaseAmount,
            'salesDocsCount' => (int) ($salesAgg?->docs ?? 0),
            'purchaseDocsCount' => (int) ($purchaseAgg?->docs ?? 0),
            'lastSalesDate' => $lastSalesDate,
            'lastPurchaseDate' => $lastPurchaseDate,
            'avgSalePrice' => $avgSalePrice,
            'stockValorisation' => $stockValorisation,
            'rotation90d' => $rotation90d,
            'topClients' => $topClients,
            'topSuppliers' => $topSuppliers,
            'lastDocuments' => $lastDocuments,
            'stockMovements' => $stockMovements,
            'chartData' => ['labels' => $chartLabels, 'sales' => $salesSeries, 'purchases' => $purchaseSeries],
        ];
    }

    protected function validateArticle(Request $request, ?int $articleId = null): array
    {
        $data = $request->validate([
            'ar_ref' => ['required', 'string', 'max:100', 'unique:f_articles,ar_ref,'.($articleId ?? 'NULL').',id'],
            'code_article' => ['nullable', 'string', 'max:100', Rule::unique('f_articles', 'code_article')->ignore($articleId)],
            'ar_design' => ['required', 'string', 'max:255'],
            'ar_code_barre' => ['nullable', 'string', 'max:255', Rule::unique('f_articles', 'ar_code_barre')->ignore($articleId)],
            'family_id' => ['nullable', 'exists:f_familles,id'],
            'ar_prix_achat' => ['required', 'numeric', 'min:0'],
            'ar_prix_vente' => ['required', 'numeric', 'min:0'],
            'ar_prix_revient' => ['nullable', 'numeric', 'min:0'],
            'ar_tva' => ['required', 'numeric', 'min:0', 'max:100'],
            'ar_stock_min' => ['required', 'numeric', 'min:0'],
            'ar_stock_actuel' => ['required', 'numeric', 'min:0'],
            'ar_suivi_stock' => ['nullable', 'boolean'],
            'ar_unite' => ['required', 'string', 'max:50'],
        ]);

        $data['code_article'] = $data['code_article'] ?? $data['ar_ref'];
        $data['ar_prix_revient'] = array_key_exists('ar_prix_revient', $data) && $data['ar_prix_revient'] !== null
            ? $data['ar_prix_revient']
            : $data['ar_prix_achat'];
        $data['ar_suivi_stock'] = $request->boolean('ar_suivi_stock');

        return $data;
    }
}
