<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport {{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color:#222; font-size:12px; }
        h1 { font-size:18px; margin-bottom:8px; }
        .meta { margin-bottom:12px; }
        pre { white-space: pre-wrap; font-size:11px; background:#f6f6f6; border:1px solid #ddd; padding:10px; }
    </style>
</head>
<body>
    <h1>Rapport IA: {{ $title }}</h1>
    <div class="meta">Periode: {{ $from }} - {{ $to }}</div>
    <pre>{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
</body>
</html>

