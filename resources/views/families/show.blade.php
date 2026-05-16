@extends('adminlte::page')

@section('title', 'Detail Famille')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="m-0">{{ $family->fa_intitule }}</h1>
            <small class="text-muted">Code: {{ $family->fa_code }}</small>
        </div>
        <div>
            <a href="{{ route('families.edit', $family) }}" class="btn btn-primary"><i class="fas fa-pen mr-1"></i> Modifier</a>
            <a href="{{ route('families.index') }}" class="btn btn-default">Retour</a>
        </div>
    </div>
@stop

@section('content')
    @include('partials.flash')

    <ul class="nav nav-tabs">
        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#fam-commercial">Vue commerciale</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#fam-stock">Vue stock</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#fam-history">Historique</a></li>
    </ul>

    <div class="tab-content pt-3">
        <div class="tab-pane fade show active" id="fam-commercial">
            <div class="row">
                <div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3>{{ number_format($salesAmount, 2, ',', ' ') }}</h3><p>CA famille</p></div><div class="icon"><i class="fas fa-chart-line"></i></div></div></div>
                <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3>{{ number_format($purchaseAmount, 2, ',', ' ') }}</h3><p>Achats famille</p></div><div class="icon"><i class="fas fa-shopping-cart"></i></div></div></div>
                <div class="col-lg-3 col-6"><div class="small-box {{ $margin >= 0 ? 'bg-primary' : 'bg-danger' }}"><div class="inner"><h3>{{ number_format($margin, 2, ',', ' ') }}</h3><p>Marge brute</p></div><div class="icon"><i class="fas fa-coins"></i></div></div></div>
                <div class="col-lg-3 col-6"><div class="small-box bg-secondary"><div class="inner"><h3>{{ $articlesCount }}</h3><p>Articles actifs</p></div><div class="icon"><i class="fas fa-cubes"></i></div></div></div>
            </div>

            <div class="card card-outline card-primary">
                <div class="card-header"><h3 class="card-title">Tendance 6 mois (ventes/achats)</h3></div>
                <div class="card-body"><canvas id="familyTrendChart" height="110"></canvas></div>
            </div>

            <div class="card card-outline card-success">
                <div class="card-header"><h3 class="card-title">Top articles de la famille</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead><tr><th>Article</th><th class="text-right">Qte</th><th class="text-right">Montant</th></tr></thead>
                        <tbody>
                        @forelse($topArticles as $row)
                            <tr><td>{{ $row->name }}</td><td class="text-right">{{ number_format((float) $row->qty, 3, ',', ' ') }}</td><td class="text-right">{{ number_format((float) $row->amount, 2, ',', ' ') }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">Aucune donnee.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="fam-stock">
            <div class="row">
                <div class="col-lg-4 col-6"><div class="small-box bg-dark"><div class="inner"><h3>{{ number_format($stockReel, 3, ',', ' ') }}</h3><p>Stock reel total</p></div><div class="icon"><i class="fas fa-warehouse"></i></div></div></div>
                <div class="col-lg-4 col-6"><div class="small-box bg-warning"><div class="inner"><h3>{{ number_format($stockReserve, 3, ',', ' ') }}</h3><p>Stock reserve total</p></div><div class="icon"><i class="fas fa-box-open"></i></div></div></div>
                <div class="col-lg-4 col-6"><div class="small-box {{ $lowStockCount > 0 ? 'bg-danger' : 'bg-success' }}"><div class="inner"><h3>{{ $lowStockCount }}</h3><p>Articles en alerte</p></div><div class="icon"><i class="fas fa-exclamation-triangle"></i></div></div></div>
            </div>

            <div class="card card-outline card-info">
                <div class="card-header"><h3 class="card-title">Articles de la famille</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>Code</th><th>Designation</th><th class="text-right">Prix vente</th><th class="text-right">Stock</th><th class="text-right">Min</th></tr></thead>
                        <tbody>
                        @forelse($family->articles as $article)
                            <tr>
                                <td><a href="{{ route('articles.show', $article) }}">{{ $article->code_article ?: $article->ar_ref }}</a></td>
                                <td>{{ $article->ar_design }}</td>
                                <td class="text-right">{{ number_format((float) $article->ar_prix_vente, 2, ',', ' ') }}</td>
                                <td class="text-right">{{ number_format((float) $article->ar_stock_actuel, 3, ',', ' ') }}</td>
                                <td class="text-right">{{ number_format((float) $article->ar_stock_min, 3, ',', ' ') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Aucun article.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="fam-history">
            <div class="card card-outline card-warning">
                <div class="card-header"><h3 class="card-title">Historique documents famille</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead><tr><th>Date</th><th>Piece</th><th>Article</th><th>Tiers</th><th>Module</th><th class="text-right">Qte</th><th class="text-right">TTC ligne</th></tr></thead>
                        <tbody>
                        @forelse($recentDocuments as $row)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($row->do_date)->format('Y-m-d') }}</td>
                                <td><a href="{{ route('documents.show', $row->doc_id) }}">{{ $row->do_piece }}</a></td>
                                <td>{{ $row->article_name }}</td>
                                <td>{{ $row->tier_name ?: '-' }}</td>
                                <td>{{ strtoupper((string) $row->doc_module) }}</td>
                                <td class="text-right">{{ number_format((float) $row->dl_qte, 3, ',', ' ') }}</td>
                                <td class="text-right">{{ number_format((float) $row->dl_montant_ttc, 2, ',', ' ') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">Aucun historique document.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop

@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartData = @json($chartData);
    new Chart(document.getElementById('familyTrendChart'), {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [
                { label: 'Ventes', data: chartData.sales, borderColor: '#28a745', backgroundColor: 'rgba(40,167,69,.12)', fill: true, tension: .35 },
                { label: 'Achats', data: chartData.purchases, borderColor: '#007bff', backgroundColor: 'rgba(0,123,255,.12)', fill: true, tension: .35 },
            ]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
});
</script>
@endpush

