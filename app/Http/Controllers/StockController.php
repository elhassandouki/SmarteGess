<?php

namespace App\Http\Controllers;

use App\Models\Depot;
use App\Models\Stock;
use App\Services\StockMovementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockController extends Controller
{
    protected StockMovementService $stockMovementService;

    public function __construct(StockMovementService $stockMovementService)
    {
        $this->middleware('auth');
        $this->stockMovementService = $stockMovementService;
    }

    public function index(Request $request): View
    {
        $depotId = $request->integer('depot_id') ?: null;
        $lowOnly = $request->boolean('low_only');
        $search = trim((string) $request->string('search'));

        $stocks = Stock::with(['article.family', 'depot'])
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('article', function ($articleQuery) use ($search) {
                    $articleQuery->where(function ($subQuery) use ($search) {
                        $subQuery
                            ->where('code_article', 'like', '%'.$search.'%')
                            ->orWhere('ar_ref', 'like', '%'.$search.'%')
                            ->orWhere('ar_design', 'like', '%'.$search.'%');
                    });
                });
            })
            ->when($depotId, fn ($query) => $query->where('depot_id', $depotId))
            ->when($lowOnly, function ($query) {
                $query->whereHas('article', fn ($articleQuery) => $articleQuery->whereColumn('ar_stock_actuel', '<=', 'ar_stock_min'));
            })
            ->orderByDesc('updated_at')
            ->get();

        return view('stocks.index', [
            'stocks' => $stocks,
            'depots' => Depot::orderBy('intitule')->get(),
            'filters' => [
                'depot_id' => $depotId,
                'low_only' => $lowOnly,
                'search' => $search,
            ],
        ]);
    }

    public function adjust(Request $request, Stock $stock): RedirectResponse
    {
        $data = $request->validate([
            'stock_reel' => ['required', 'numeric', 'min:0'],
            'stock_reserve' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $reason = $data['reason'] ?? 'Manual stock adjustment';

        DB::transaction(function () use ($stock, $data, $reason) {
            $this->stockMovementService->adjustStock(
                $stock,
                (float) $data['stock_reel'],
                $reason
            );

            // Also update reserve if provided
            if (isset($data['stock_reserve'])) {
                $stock->update(['stock_reserve' => $data['stock_reserve']]);
            }
        });

        return redirect()->route('stocks.index')->with('success', 'Stock ajuste avec succes.');
    }
}
