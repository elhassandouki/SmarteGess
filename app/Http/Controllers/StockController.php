<?php

namespace App\Http\Controllers;

use App\Models\Depot;
use App\Models\Stock;
use App\Services\StockMovementService;
use Illuminate\Http\JsonResponse;
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

    public function index(Request $request): View|JsonResponse
    {
        $depotId = $request->integer('depot_id') ?: null;
        $lowOnly = $request->boolean('low_only');
        $search = trim((string) $request->string('search'));

        $query = Stock::with(['article.family', 'depot'])
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
            ->orderByDesc('updated_at');

        if ($request->ajax() && $request->has('draw')) {
            $draw = (int) $request->input('draw', 1);
            $start = max(0, (int) $request->input('start', 0));
            $length = max(10, (int) $request->input('length', 10));
            $recordsTotal = (clone $query)->count();
            $recordsFiltered = $recordsTotal;
            $rows = $query->skip($start)->take($length)->get();

            $data = $rows->map(function (Stock $stock) {
                return [
                    'depot' => e($stock->depot?->intitule ?? '-'),
                    'code' => e($stock->article?->code_article ?: $stock->article?->ar_ref ?: '-'),
                    'article' => e($stock->article?->ar_design ?? '-'),
                    'famille' => e($stock->article?->family?->fa_intitule ?? '-'),
                    'stock_reel' => number_format((float) $stock->stock_reel, 3),
                    'stock_reserve' => number_format((float) $stock->stock_reserve, 3),
                    'stock_min' => number_format((float) ($stock->article?->ar_stock_min ?? 0), 3),
                    'valorisation' => number_format((float) $stock->stock_reel * (float) ($stock->article?->ar_prix_achat ?? 0), 2),
                    'ajustement' => '<form method="POST" action="'.route('stocks.adjust', $stock).'" class="form-inline justify-content-center" data-ajax="true" data-reload="true">'.csrf_field().method_field('PATCH').'<input type="number" step="0.001" min="0" name="stock_reel" value="'.$stock->stock_reel.'" class="form-control form-control-sm mr-2" style="width:92px;"><input type="number" step="0.001" min="0" name="stock_reserve" value="'.$stock->stock_reserve.'" class="form-control form-control-sm mr-2" style="width:92px;"><input type="hidden" name="reason" value="Ajustement rapide depuis tableau stock"><button type="submit" class="btn btn-xs btn-outline-primary">MAJ</button></form>',
                ];
            })->all();

            return response()->json(compact('draw', 'recordsTotal', 'recordsFiltered', 'data'));
        }

        $stocks = $query->get();

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

    public function adjust(Request $request, Stock $stock): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'stock_reel' => ['required', 'numeric', 'min:0'],
            'stock_reserve' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if (isset($data['stock_reserve']) && (float) $data['stock_reel'] < (float) $data['stock_reserve']) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Le stock reel ne peut pas etre inferieur au stock reserve.'], 422)
                : back()->withErrors(['stock_reel' => 'Le stock reel ne peut pas etre inferieur au stock reserve.'])->withInput();
        }

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

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Stock ajuste avec succes.',
            ]);
        }

        return redirect()->route('stocks.index')->with('success', 'Stock ajuste avec succes.');
    }
}
