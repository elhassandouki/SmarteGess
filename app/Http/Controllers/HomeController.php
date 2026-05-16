<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\CompteT;
use App\Models\Document;
use App\Models\Reglement;
use App\Models\Stock;
use App\Models\Family;
use App\Models\Transporteur;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(): Renderable
    {
        $from = now()->startOfMonth()->subMonths(5);
        $to = now()->endOfMonth();

        $monthlyRows = Document::query()
            ->selectRaw("DATE_FORMAT(do_date, '%Y-%m') as ym")
            ->selectRaw('SUM(CASE WHEN doc_module = "sales" THEN do_total_ttc ELSE 0 END) as sales_total')
            ->selectRaw('SUM(CASE WHEN doc_module = "purchase" THEN do_total_ttc ELSE 0 END) as purchases_total')
            ->whereBetween('do_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->keyBy('ym');

        $months = collect(range(0, 5))->map(function (int $i) use ($from) {
            return $from->copy()->addMonths($i);
        });

        $monthlyLabels = $months->map(fn (Carbon $date) => $date->format('M Y'))->all();
        $salesTrend = $months->map(function (Carbon $date) use ($monthlyRows) {
            $key = $date->format('Y-m');
            return (float) ($monthlyRows[$key]->sales_total ?? 0);
        })->all();
        $purchasesTrend = $months->map(function (Carbon $date) use ($monthlyRows) {
            $key = $date->format('Y-m');
            return (float) ($monthlyRows[$key]->purchases_total ?? 0);
        })->all();

        $documentMix = Document::query()
            ->selectRaw('COALESCE(type_document_code, "BC") as type_document_code, COUNT(*) as total')
            ->groupBy('type_document_code')
            ->orderByDesc('total')
            ->get();

        $caByDocumentType = Document::query()
            ->selectRaw('COALESCE(type_document_code, "BC") as type_document_code')
            ->selectRaw('SUM(CASE WHEN doc_module = "sales" THEN do_total_ttc ELSE 0 END) as ca_total')
            ->groupBy('type_document_code')
            ->orderByDesc('ca_total')
            ->get();

        $paymentRows = Reglement::query()
            ->selectRaw("DATE_FORMAT(rg_date, '%Y-%m') as ym")
            ->selectRaw('SUM(rg_montant) as total')
            ->whereBetween('rg_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->keyBy('ym');

        $paymentsTrend = $months->map(function (Carbon $date) use ($paymentRows) {
            return (float) ($paymentRows[$date->format('Y-m')]->total ?? 0);
        })->all();

        $topClients = Document::query()
            ->join('f_comptet', 'f_comptet.id', '=', 'f_docentete.tier_id')
            ->selectRaw('f_comptet.ct_intitule as name, SUM(f_docentete.do_total_ttc) as total')
            ->where('f_docentete.doc_module', 'sales')
            ->groupBy('f_comptet.id', 'f_comptet.ct_intitule')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $topArticles = DB::table('f_docligne')
            ->join('f_articles', 'f_articles.id', '=', 'f_docligne.article_id')
            ->selectRaw('f_articles.ar_design as name, SUM(f_docligne.dl_qte) as qty')
            ->groupBy('f_articles.id', 'f_articles.ar_design')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        $salesTotal = Document::where('doc_module', 'sales')->sum('do_total_ttc');
        $purchasesTotal = Document::where('doc_module', 'purchase')->sum('do_total_ttc');
        $paymentsTotal = Reglement::sum('rg_montant');

        return view('home', [
            'stats' => [
                'families' => Family::count(),
                'articles' => Article::count(),
                'tiers' => CompteT::count(),
                'transporteurs' => Transporteur::count(),
                'documents' => Document::count(),
                'stocks' => Stock::count(),
                'reglements' => Reglement::count(),
                'taxes' => DB::table('f_taxes')->count(),
                'depots' => DB::table('f_depots')->count(),
                'sales_total' => $salesTotal,
                'purchases_total' => $purchasesTotal,
                'payments_total' => $paymentsTotal,
                'receivable' => max($salesTotal - $paymentsTotal, 0),
            ],
            'recentDocuments' => Document::with(['transporteur', 'tier'])
                ->latest('do_date')
                ->take(5)
                ->get(),
            'recentReglements' => Reglement::with(['tier', 'document'])
                ->latest('rg_date')
                ->take(5)
                ->get(),
            'lowStockArticles' => Article::query()
                ->where('ar_suivi_stock', true)
                ->whereColumn('ar_stock_actuel', '<=', 'ar_stock_min')
                ->orderBy('ar_stock_actuel')
                ->take(5)
                ->get(),
            'documentTypes' => [
                'DE' => 'Devis',
                'BC' => 'Bon de commande',
                'BL' => 'Bon de livraison',
                'FA' => 'Facture',
                'BR' => 'Bon de retour',
                'FR' => 'Facture retour',
            ],
            'chartData' => [
                'monthly_labels' => $monthlyLabels,
                'sales_trend' => $salesTrend,
                'purchases_trend' => $purchasesTrend,
                'payments_trend' => $paymentsTrend,
                'document_mix_labels' => $documentMix->pluck('type_document_code')->all(),
                'document_mix_values' => $documentMix->pluck('total')->map(fn ($v) => (int) $v)->all(),
            ],
            'topClients' => $topClients,
            'topArticles' => $topArticles,
            'caByDocumentType' => $caByDocumentType,
        ]);
    }
}
