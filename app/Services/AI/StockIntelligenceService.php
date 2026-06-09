<?php

namespace App\Services\AI;

use App\Models\Article;
use App\Models\DocumentLine;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StockIntelligenceService
{
    public function alerts(int $lookbackDays = 90, int $coverageDays = 14): array
    {
        $since = now()->subDays(max(7, $lookbackDays))->startOfDay();
        $articles = Article::query()->where('ar_suivi_stock', true)->get();

        $avgDailyConsumption = $this->averageDailyConsumptionByArticle($since);

        $rows = Stock::query()
            ->with(['article', 'depot'])
            ->get()
            ->map(function (Stock $stock) use ($avgDailyConsumption, $coverageDays): array {
                $article = $stock->article;
                $daily = (float) ($avgDailyConsumption[$stock->article_id] ?? 0.0);
                $reorderPoint = max((float) ($article?->ar_stock_min ?? 0), $daily * max(1, $coverageDays));
                $current = (float) $stock->stock_reel;
                $needed = max(0.0, $reorderPoint - $current);

                return [
                    'article_id' => $stock->article_id,
                    'article' => $article?->ar_design ?? 'Article inconnu',
                    'code' => $article?->code_article ?: $article?->ar_ref,
                    'depot_id' => $stock->depot_id,
                    'depot' => $stock->depot?->intitule ?? 'Depot inconnu',
                    'current_stock' => $current,
                    'minimum_stock' => (float) ($article?->ar_stock_min ?? 0),
                    'predicted_daily_consumption' => $daily,
                    'predicted_reorder_point' => $reorderPoint,
                    'suggested_reorder_qty' => $needed,
                    'status' => $current <= $reorderPoint ? 'alert' : 'ok',
                ];
            })
            ->filter(fn (array $row) => $row['status'] === 'alert')
            ->sortBy('current_stock')
            ->values();

        return [
            'rows' => $rows->all(),
            'insights' => [
                'alert_count' => $rows->count(),
                'summary' => $rows->isEmpty()
                    ? 'Aucun risque de rupture detecte.'
                    : sprintf('%d lignes de stock a risque detectees.', $rows->count()),
            ],
            'params' => [
                'lookback_days' => $lookbackDays,
                'coverage_days' => $coverageDays,
            ],
        ];
    }

    private function averageDailyConsumptionByArticle(Carbon $since): Collection
    {
        $consumptionByArticle = DocumentLine::query()
            ->join('f_docentete', 'f_docentete.id', '=', 'f_docligne.doc_id')
            ->where('f_docentete.do_date', '>=', $since->toDateString())
            ->whereIn('f_docentete.type_document_code', ['BL'])
            ->selectRaw('f_docligne.article_id, COALESCE(SUM(f_docligne.dl_qte), 0) as qty')
            ->groupBy('f_docligne.article_id')
            ->pluck('qty', 'article_id');

        $days = max(1, $since->diffInDays(now()));

        return $consumptionByArticle->map(fn ($qty) => (float) $qty / $days);
    }
}

