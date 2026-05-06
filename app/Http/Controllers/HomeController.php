<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\CompteT;
use App\Models\Document;
use App\Models\Reglement;
use App\Models\Stock;
use App\Models\Family;
use App\Models\Transporteur;
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
        ]);
    }
}
