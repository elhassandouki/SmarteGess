<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Document {{ $document->do_piece }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color:#222; font-size:12px; }
        h1 { margin:0 0 8px; font-size:18px; }
        .meta { margin-bottom: 12px; }
        .summary { background:#f6f6f6; padding:8px; margin-bottom:12px; }
        table { width:100%; border-collapse: collapse; }
        th, td { border:1px solid #ddd; padding:6px; }
        th { background:#efefef; text-align:left; }
        .num { text-align:right; }
    </style>
</head>
<body>
    <h1>{{ $document->type_document_code }} - {{ $document->do_piece }}</h1>
    <div class="meta">
        <div>Date: {{ optional($document->do_date)->format('d/m/Y') }}</div>
        <div>Tiers: {{ $document->tier?->ct_intitule ?? '-' }}</div>
        <div>Depot: {{ $document->depot?->intitule ?? '-' }}</div>
    </div>
    <div class="summary"><strong>Resume IA:</strong> {{ $aiSummary }}</div>
    <table>
        <thead>
            <tr>
                <th>Article</th>
                <th>Reference</th>
                <th class="num">Qte</th>
                <th class="num">PU HT</th>
                <th class="num">Montant HT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($document->lines as $line)
                <tr>
                    <td>{{ $line->article?->ar_design ?? '-' }}</td>
                    <td>{{ $line->article?->code_article ?: $line->article?->ar_ref ?: '-' }}</td>
                    <td class="num">{{ number_format((float) $line->dl_qte, 3, ',', ' ') }}</td>
                    <td class="num">{{ number_format((float) $line->dl_prix_unitaire_ht, 2, ',', ' ') }}</td>
                    <td class="num">{{ number_format((float) $line->dl_montant_ht, 2, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p><strong>Total TTC:</strong> {{ number_format((float) $document->do_total_ttc, 2, ',', ' ') }}</p>
</body>
</html>

