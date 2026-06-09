<?php

namespace App\Services\AI;

use App\Models\Document;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BusinessAnalyticsService
{
    public function revenueByCustomer(Carbon $from, Carbon $to): array
    {
        $rows = Document::query()
            ->whereBetween('do_date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('type_document_code', ['FA'])
            ->selectRaw('tier_id, SUM(do_total_ht) as revenue_ht, SUM(do_total_ttc) as revenue_ttc, COUNT(*) as invoices_count')
            ->groupBy('tier_id')
            ->with('tier:id,ct_intitule,code_tiers,ct_num')
            ->orderByDesc('revenue_ttc')
            ->get();

        return [
            'rows' => $rows->map(function (Document $doc): array {
                return [
                    'tier_id' => $doc->tier_id,
                    'customer' => $doc->tier?->ct_intitule ?? 'Client inconnu',
                    'code' => $doc->tier?->code_tiers ?: $doc->tier?->ct_num,
                    'revenue_ht' => (float) $doc->revenue_ht,
                    'revenue_ttc' => (float) $doc->revenue_ttc,
                    'invoices_count' => (int) $doc->invoices_count,
                ];
            })->values()->all(),
            'insights' => $this->buildRevenueInsights($rows),
        ];
    }

    public function stockValuationByArticle(): array
    {
        $rows = Stock::query()
            ->join('f_articles', 'f_articles.id', '=', 'f_stock.article_id')
            ->selectRaw('f_stock.article_id, f_articles.ar_design, f_articles.code_article, f_articles.ar_ref, SUM(f_stock.stock_reel) as stock_qty, AVG(f_articles.ar_prix_achat) as unit_cost')
            ->groupBy('f_stock.article_id', 'f_articles.ar_design', 'f_articles.code_article', 'f_articles.ar_ref')
            ->get();

        $mapped = $rows->map(function ($row): array {
            $qty = (float) $row->stock_qty;
            $unitCost = (float) $row->unit_cost;
            return [
                'article_id' => (int) $row->article_id,
                'article' => $row->ar_design,
                'code' => $row->code_article ?: $row->ar_ref,
                'stock_qty' => $qty,
                'unit_cost' => $unitCost,
                'stock_value' => $qty * $unitCost,
            ];
        })->sortByDesc('stock_value')->values();

        return [
            'rows' => $mapped->all(),
            'insights' => $this->buildStockValuationInsights($mapped),
        ];
    }

    public function outstandingReceivables(): array
    {
        $rows = Document::query()
            ->with('tier:id,ct_intitule,code_tiers,ct_num')
            ->whereIn('type_document_code', ['FA'])
            ->whereRaw('COALESCE(do_total_ttc, 0) > COALESCE(do_montant_regle, 0)')
            ->orderBy('do_date')
            ->get();

        $mapped = $rows->map(function (Document $doc): array {
            $outstanding = max(0.0, (float) $doc->do_total_ttc - (float) $doc->do_montant_regle);
            return [
                'document_id' => $doc->id,
                'piece' => $doc->do_piece,
                'date' => optional($doc->do_date)->toDateString(),
                'customer' => $doc->tier?->ct_intitule ?? 'Client inconnu',
                'code' => $doc->tier?->code_tiers ?: $doc->tier?->ct_num,
                'total_ttc' => (float) $doc->do_total_ttc,
                'paid' => (float) $doc->do_montant_regle,
                'outstanding' => $outstanding,
            ];
        })->values();

        return [
            'rows' => $mapped->all(),
            'insights' => $this->buildReceivablesInsights($mapped),
        ];
    }

    public function periodComparison(Carbon $from, Carbon $to): array
    {
        $days = max(1, $from->diffInDays($to) + 1);
        $previousFrom = $from->copy()->subDays($days);
        $previousTo = $to->copy()->subDays($days);

        $current = $this->periodKpis($from, $to);
        $previous = $this->periodKpis($previousFrom, $previousTo);

        return [
            'current_period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'kpis' => $current,
            ],
            'previous_period' => [
                'from' => $previousFrom->toDateString(),
                'to' => $previousTo->toDateString(),
                'kpis' => $previous,
            ],
            'insights' => $this->buildComparisonInsights($current, $previous),
        ];
    }

    private function periodKpis(Carbon $from, Carbon $to): array
    {
        $sales = Document::query()
            ->whereBetween('do_date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('type_document_code', ['FA'])
            ->selectRaw('COALESCE(SUM(do_total_ttc), 0) as revenue, COUNT(*) as invoices')
            ->first();

        $purchases = Document::query()
            ->whereBetween('do_date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('type_document_code', ['BR', 'FR'])
            ->selectRaw('COALESCE(SUM(do_total_ttc), 0) as purchases')
            ->first();

        return [
            'revenue' => (float) ($sales?->revenue ?? 0),
            'invoices' => (int) ($sales?->invoices ?? 0),
            'purchases' => (float) ($purchases?->purchases ?? 0),
        ];
    }

    private function buildRevenueInsights(Collection $rows): array
    {
        $total = (float) $rows->sum('revenue_ttc');
        $top = $rows->first();

        return [
            'summary' => sprintf('CA TTC total: %.2f', $total),
            'top_customer' => $top ? ($top->tier?->ct_intitule ?? 'Client inconnu') : null,
            'top_customer_revenue_ttc' => $top ? (float) $top->revenue_ttc : 0.0,
        ];
    }

    private function buildStockValuationInsights(Collection $rows): array
    {
        $total = (float) $rows->sum('stock_value');
        $top = $rows->first();

        return [
            'summary' => sprintf('Valorisation globale du stock: %.2f', $total),
            'highest_value_article' => $top['article'] ?? null,
            'highest_value_amount' => (float) ($top['stock_value'] ?? 0),
        ];
    }

    private function buildReceivablesInsights(Collection $rows): array
    {
        $outstanding = (float) $rows->sum('outstanding');
        $largest = $rows->sortByDesc('outstanding')->first();

        return [
            'summary' => sprintf('Encours client total: %.2f', $outstanding),
            'largest_due_customer' => $largest['customer'] ?? null,
            'largest_due_amount' => (float) ($largest['outstanding'] ?? 0),
        ];
    }

    private function buildComparisonInsights(array $current, array $previous): array
    {
        $deltaRevenue = (float) $current['revenue'] - (float) $previous['revenue'];
        $deltaInvoices = (int) $current['invoices'] - (int) $previous['invoices'];

        return [
            'delta_revenue' => $deltaRevenue,
            'delta_invoices' => $deltaInvoices,
            'trend' => $deltaRevenue >= 0 ? 'up' : 'down',
            'summary' => sprintf(
                'Variation CA: %.2f | Variation factures: %d',
                $deltaRevenue,
                $deltaInvoices
            ),
        ];
    }
}
