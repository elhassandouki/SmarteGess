<?php

namespace App\Http\Controllers;

use App\Models\Family;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FamilyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->string('search'));
        $minArticles = $request->integer('min_articles');

        $query = Family::withCount('articles')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('fa_code', 'like', '%'.$search.'%')
                        ->orWhere('fa_intitule', 'like', '%'.$search.'%');
                });
            })
            ->when($minArticles > 0, fn ($query) => $query->having('articles_count', '>=', $minArticles));

        if ($request->ajax() && $request->has('draw')) {
            $draw = (int) $request->input('draw', 1);
            $start = max(0, (int) $request->input('start', 0));
            $length = max(10, (int) $request->input('length', 10));
            $recordsTotal = (clone $query)->count();
            $recordsFiltered = $recordsTotal;
            $rows = $query->orderByDesc('id')->skip($start)->take($length)->get();

            $data = $rows->map(function (Family $family) {
                return [
                    'code' => e($family->fa_code),
                    'intitule' => e($family->fa_intitule),
                    'articles_count' => (int) $family->articles_count,
                    'actions' => '<div class="btn-group btn-group-sm" role="group">'
                        .'<a href="'.route('families.show', $family).'" class="btn btn-xs btn-outline-secondary mr-2"><i class="fas fa-eye"></i></a>'
                        .'<a href="'.route('families.edit', $family).'" class="btn btn-xs btn-outline-primary mr-2"><i class="fas fa-pen"></i></a>'
                        .'<form action="'.route('families.destroy', $family).'" method="POST" onsubmit="return confirm(\'Supprimer cette famille ?\');">'.csrf_field().method_field('DELETE').'<button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form>'
                        .'</div>',
                ];
            })->all();

            return response()->json(compact('draw', 'recordsTotal', 'recordsFiltered', 'data'));
        }

        $families = $query->latest()->get();

        return view('families.index', [
            'families' => $families,
            'filters' => [
                'search' => $search,
                'min_articles' => $minArticles,
            ],
        ]);
    }

    public function create(): View
    {
        return view('families.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fa_code' => ['required', 'string', 'max:50', 'unique:f_familles,fa_code'],
            'fa_intitule' => ['required', 'string', 'max:255'],
        ]);

        Family::create($data);

        return redirect()->route('families.index')->with('success', 'Famille creee avec succes.');
    }

    public function edit(Family $family): View
    {
        return view('families.edit', compact('family'));
    }

    public function update(Request $request, Family $family): RedirectResponse
    {
        $data = $request->validate([
            'fa_code' => ['required', 'string', 'max:50', 'unique:f_familles,fa_code,'.$family->id],
            'fa_intitule' => ['required', 'string', 'max:255'],
        ]);

        $family->update($data);

        return redirect()->route('families.index')->with('success', 'Famille mise a jour avec succes.');
    }

    public function destroy(Family $family): RedirectResponse
    {
        if ($family->articles()->exists()) {
            return redirect()->route('families.index')->with('error', 'Impossible de supprimer une famille qui contient des articles.');
        }

        $family->delete();

        return redirect()->route('families.index')->with('success', 'Famille supprimee avec succes.');
    }

    public function show(Family $family): View
    {
        $family->load('articles');

        $articleIds = $family->articles->pluck('id');

        $salesAgg = DB::table('f_docligne')
            ->join('f_docentete', 'f_docentete.id', '=', 'f_docligne.doc_id')
            ->whereIn('f_docligne.article_id', $articleIds)
            ->where('f_docentete.doc_module', 'sales')
            ->selectRaw('SUM(f_docligne.dl_qte) as qty, SUM(f_docligne.dl_montant_ttc) as amount, COUNT(DISTINCT f_docentete.id) as docs')
            ->first();

        $purchaseAgg = DB::table('f_docligne')
            ->join('f_docentete', 'f_docentete.id', '=', 'f_docligne.doc_id')
            ->whereIn('f_docligne.article_id', $articleIds)
            ->where('f_docentete.doc_module', 'purchase')
            ->selectRaw('SUM(f_docligne.dl_qte) as qty, SUM(f_docligne.dl_montant_ttc) as amount, COUNT(DISTINCT f_docentete.id) as docs')
            ->first();

        $stockAgg = DB::table('f_stock')
            ->join('f_articles', 'f_articles.id', '=', 'f_stock.article_id')
            ->whereIn('f_stock.article_id', $articleIds)
            ->selectRaw('SUM(f_stock.stock_reel) as stock_reel, SUM(f_stock.stock_reserve) as stock_reserve')
            ->first();

        $topArticles = DB::table('f_docligne')
            ->join('f_articles', 'f_articles.id', '=', 'f_docligne.article_id')
            ->join('f_docentete', 'f_docentete.id', '=', 'f_docligne.doc_id')
            ->whereIn('f_docligne.article_id', $articleIds)
            ->selectRaw('f_articles.ar_design as name, SUM(f_docligne.dl_qte) as qty, SUM(f_docligne.dl_montant_ttc) as amount')
            ->groupBy('f_articles.id', 'f_articles.ar_design')
            ->orderByDesc('amount')
            ->limit(8)
            ->get();

        $recentDocuments = DB::table('f_docligne')
            ->join('f_docentete', 'f_docentete.id', '=', 'f_docligne.doc_id')
            ->join('f_articles', 'f_articles.id', '=', 'f_docligne.article_id')
            ->leftJoin('f_comptet', 'f_comptet.id', '=', 'f_docentete.tier_id')
            ->whereIn('f_docligne.article_id', $articleIds)
            ->selectRaw('f_docentete.id as doc_id, f_docentete.do_piece, f_docentete.do_date, f_docentete.doc_module, f_docentete.type_document_code, f_docentete.do_total_ttc, f_articles.ar_design as article_name, f_docligne.dl_qte, f_docligne.dl_montant_ttc, f_comptet.ct_intitule as tier_name')
            ->orderByDesc('f_docentete.do_date')
            ->limit(20)
            ->get();

        $from = now()->startOfMonth()->subMonths(5);
        $monthlyRows = DB::table('f_docligne')
            ->join('f_docentete', 'f_docentete.id', '=', 'f_docligne.doc_id')
            ->whereIn('f_docligne.article_id', $articleIds)
            ->whereBetween('f_docentete.do_date', [$from->toDateString(), now()->endOfMonth()->toDateString()])
            ->selectRaw("DATE_FORMAT(f_docentete.do_date, '%Y-%m') as ym")
            ->selectRaw('SUM(CASE WHEN f_docentete.doc_module = "sales" THEN f_docligne.dl_montant_ttc ELSE 0 END) as sales_total')
            ->selectRaw('SUM(CASE WHEN f_docentete.doc_module = "purchase" THEN f_docligne.dl_montant_ttc ELSE 0 END) as purchases_total')
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->keyBy('ym');

        $months = collect(range(0, 5))->map(fn (int $i) => $from->copy()->addMonths($i));
        $labels = $months->map(fn (Carbon $d) => $d->format('M Y'))->all();
        $salesSeries = $months->map(fn (Carbon $d) => (float) ($monthlyRows[$d->format('Y-m')]->sales_total ?? 0))->all();
        $purchasesSeries = $months->map(fn (Carbon $d) => (float) ($monthlyRows[$d->format('Y-m')]->purchases_total ?? 0))->all();

        $salesAmount = (float) ($salesAgg?->amount ?? 0);
        $purchaseAmount = (float) ($purchaseAgg?->amount ?? 0);
        $salesQty = (float) ($salesAgg?->qty ?? 0);
        $purchaseQty = (float) ($purchaseAgg?->qty ?? 0);

        $articlesCount = $family->articles->count();
        $lowStockCount = $family->articles->filter(fn ($a) => (float) $a->ar_stock_actuel <= (float) $a->ar_stock_min)->count();

        return view('families.show', [
            'family' => $family,
            'articlesCount' => $articlesCount,
            'lowStockCount' => $lowStockCount,
            'salesAmount' => $salesAmount,
            'purchaseAmount' => $purchaseAmount,
            'margin' => $salesAmount - $purchaseAmount,
            'salesQty' => $salesQty,
            'purchaseQty' => $purchaseQty,
            'salesDocsCount' => (int) ($salesAgg?->docs ?? 0),
            'purchaseDocsCount' => (int) ($purchaseAgg?->docs ?? 0),
            'stockReel' => (float) ($stockAgg?->stock_reel ?? 0),
            'stockReserve' => (float) ($stockAgg?->stock_reserve ?? 0),
            'topArticles' => $topArticles,
            'recentDocuments' => $recentDocuments,
            'chartData' => [
                'labels' => $labels,
                'sales' => $salesSeries,
                'purchases' => $purchasesSeries,
            ],
        ]);
    }
}
