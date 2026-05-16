<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Article Report - {{ $article->code_article ?: $article->ar_ref }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #222; }
        h1, h2 { margin: 0 0 8px; }
        .muted { color: #666; margin-bottom: 12px; }
        .grid { display: table; width: 100%; margin-bottom: 14px; }
        .cell { display: table-cell; width: 25%; border: 1px solid #ddd; padding: 8px; vertical-align: top; }
        .kpi { font-size: 18px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f3f3f3; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Fiche Article - {{ $article->ar_design }}</h1>
    <div class="muted">
        Code: {{ $article->code_article ?: $article->ar_ref }} |
        Famille: {{ $article->family?->fa_intitule ?? '-' }} |
        Genere le: {{ $generatedAt->format('Y-m-d H:i') }}
    </div>

    <div class="grid">
        <div class="cell"><div>CA ventes</div><div class="kpi">{{ number_format($salesAmount, 2, ',', ' ') }}</div></div>
        <div class="cell"><div>Total achats</div><div class="kpi">{{ number_format($purchaseAmount, 2, ',', ' ') }}</div></div>
        <div class="cell"><div>Marge brute</div><div class="kpi">{{ number_format($margin, 2, ',', ' ') }}</div></div>
        <div class="cell"><div>Stock actuel</div><div class="kpi">{{ number_format((float) $article->ar_stock_actuel, 3, ',', ' ') }}</div></div>
    </div>

    <h2>Top clients</h2>
    <table>
        <thead><tr><th>Client</th><th class="right">CA</th></tr></thead>
        <tbody>
        @forelse($topClients as $row)
            <tr><td>{{ $row->name }}</td><td class="right">{{ number_format((float) $row->amount, 2, ',', ' ') }}</td></tr>
        @empty
            <tr><td colspan="2">Aucune donnee</td></tr>
        @endforelse
        </tbody>
    </table>

    <h2>Stock par depot</h2>
    <table>
        <thead><tr><th>Depot</th><th class="right">Stock reel</th><th class="right">Reserve</th></tr></thead>
        <tbody>
        @forelse($article->stocks as $stock)
            <tr>
                <td>{{ $stock->depot?->intitule ?? '-' }}</td>
                <td class="right">{{ number_format((float) $stock->stock_reel, 3, ',', ' ') }}</td>
                <td class="right">{{ number_format((float) $stock->stock_reserve, 3, ',', ' ') }}</td>
            </tr>
        @empty
            <tr><td colspan="3">Aucune donnee</td></tr>
        @endforelse
        </tbody>
    </table>

    <h2>Derniers documents</h2>
    <table>
        <thead><tr><th>Date</th><th>Piece</th><th>Module</th><th class="right">Qte</th><th class="right">TTC</th></tr></thead>
        <tbody>
        @forelse($lastDocuments as $line)
            <tr>
                <td>{{ optional($line->document?->do_date)->format('Y-m-d') }}</td>
                <td>{{ $line->document?->do_piece }}</td>
                <td>{{ strtoupper((string) ($line->document?->doc_module ?? '-')) }}</td>
                <td class="right">{{ number_format((float) $line->dl_qte, 3, ',', ' ') }}</td>
                <td class="right">{{ number_format((float) $line->dl_montant_ttc, 2, ',', ' ') }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Aucune donnee</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>

