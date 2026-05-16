@extends('adminlte::page')

@section('title', 'Article - Detail')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="m-0">{{ $article->ar_design }}</h1>
            <small class="text-muted">{{ $article->code_article ?: $article->ar_ref }} | Famille: {{ $article->family?->fa_intitule ?? '-' }}</small>
        </div>
        <div>
            <a href="{{ route('articles.export-pdf', $article) }}" class="btn btn-danger">
                <i class="fas fa-file-pdf mr-1"></i> Export PDF
            </a>
            <a href="{{ route('articles.edit', $article) }}" class="btn btn-primary"><i class="fas fa-pen mr-1"></i> Modifier</a>
            <a href="{{ route('articles.index') }}" class="btn btn-default">Retour</a>
        </div>
    </div>
@stop

@section('content')
    @include('partials.flash')

    <ul class="nav nav-tabs" id="articleTabs" role="tablist">
        <li class="nav-item"><a class="nav-link active" id="commercial-tab" data-toggle="tab" href="#commercial" role="tab">Vue commerciale</a></li>
        <li class="nav-item"><a class="nav-link" id="stock-tab" data-toggle="tab" href="#stock" role="tab">Vue stock</a></li>
        <li class="nav-item"><a class="nav-link" id="finance-tab" data-toggle="tab" href="#finance" role="tab">Vue financiere</a></li>
    </ul>
    <div class="tab-content pt-3">
        <div class="tab-pane fade show active" id="commercial" role="tabpanel">
            <div class="row">
                <div class="col-lg-4 col-6"><div class="small-box bg-success"><div class="inner"><h3>{{ number_format($salesAmount, 2, ',', ' ') }}</h3><p>CA ventes</p></div><div class="icon"><i class="fas fa-chart-line"></i></div></div></div>
                <div class="col-lg-4 col-6"><div class="small-box bg-info"><div class="inner"><h3>{{ number_format($salesQty, 3, ',', ' ') }}</h3><p>Qte vendue</p></div><div class="icon"><i class="fas fa-cubes"></i></div></div></div>
                <div class="col-lg-4 col-6"><div class="small-box bg-primary"><div class="inner"><h3>{{ $salesDocsCount }}</h3><p>Documents vente</p></div><div class="icon"><i class="fas fa-file-invoice"></i></div></div></div>
            </div>
            <div class="card card-outline card-dark">
                <div class="card-header"><h3 class="card-title">KPIs detailles</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <tbody>
                        <tr><th>Docs ventes</th><td class="text-right">{{ $salesDocsCount }}</td></tr>
                        <tr><th>Docs achats</th><td class="text-right">{{ $purchaseDocsCount }}</td></tr>
                        <tr><th>Derniere vente</th><td class="text-right">{{ $lastSalesDate ? \Carbon\Carbon::parse($lastSalesDate)->format('Y-m-d') : '-' }}</td></tr>
                        <tr><th>Dernier achat</th><td class="text-right">{{ $lastPurchaseDate ? \Carbon\Carbon::parse($lastPurchaseDate)->format('Y-m-d') : '-' }}</td></tr>
                        <tr><th>Prix moyen vente</th><td class="text-right">{{ number_format($avgSalePrice, 2, ',', ' ') }}</td></tr>
                        <tr><th>Valorisation stock</th><td class="text-right">{{ number_format($stockValorisation, 2, ',', ' ') }}</td></tr>
                        <tr><th>Couverture stock (jours)</th><td class="text-right">{{ $rotation90d ? number_format($rotation90d, 0, ',', ' ') : '-' }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <div class="card card-outline card-primary">
                        <div class="card-header"><h3 class="card-title">Evolution ventes/achats (6 mois)</h3></div>
                        <div class="card-body"><canvas id="articleTrendChart" height="110"></canvas></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-outline card-secondary"><div class="card-header"><h3 class="card-title">Infos commerciales</h3></div>
                        <div class="card-body">
                            <p class="mb-1"><strong>Prix vente:</strong> {{ number_format((float) $article->ar_prix_vente, 2, ',', ' ') }}</p>
                            <p class="mb-1"><strong>Prix moyen vente:</strong> {{ number_format($avgSalePrice, 2, ',', ' ') }}</p>
                            <p class="mb-1"><strong>Derniere vente:</strong> {{ $lastSalesDate ? \Carbon\Carbon::parse($lastSalesDate)->format('Y-m-d') : '-' }}</p>
                            <p class="mb-0"><strong>Top clients:</strong> {{ $topClients->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="card card-outline card-success">
                        <div class="card-header"><h3 class="card-title">Top clients (article)</h3></div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-striped mb-0">
                                <thead><tr><th>Client</th><th class="text-right">CA</th></tr></thead>
                                <tbody>
                                @forelse($topClients as $c)
                                    <tr><td>{{ $c->name }}</td><td class="text-right">{{ number_format((float) $c->amount, 2, ',', ' ') }}</td></tr>
                                @empty
                                    <tr><td colspan="2" class="text-center text-muted">Pas de ventes client.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-outline card-info">
                        <div class="card-header"><h3 class="card-title">Top fournisseurs (article)</h3></div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-striped mb-0">
                                <thead><tr><th>Fournisseur</th><th class="text-right">Achat</th></tr></thead>
                                <tbody>
                                @forelse($topSuppliers as $s)
                                    <tr><td>{{ $s->name }}</td><td class="text-right">{{ number_format((float) $s->amount, 2, ',', ' ') }}</td></tr>
                                @empty
                                    <tr><td colspan="2" class="text-center text-muted">Pas d achats fournisseur.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card card-outline card-warning">
                <div class="card-header"><h3 class="card-title">Historique documents</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>Date</th><th>Piece</th><th>Module</th><th>Qte</th><th>TTC</th></tr></thead>
                        <tbody>
                        @forelse($lastDocuments as $line)
                            <tr><td>{{ optional($line->document?->do_date)->format('Y-m-d') }}</td><td><a href="{{ route('documents.show', $line->document) }}">{{ $line->document?->do_piece }}</a></td><td>{{ strtoupper((string) ($line->document?->doc_module ?? '-')) }}</td><td>{{ number_format((float) $line->dl_qte, 3, ',', ' ') }}</td><td>{{ number_format((float) $line->dl_montant_ttc, 2, ',', ' ') }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Aucun historique document.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card card-outline card-primary">
                <div class="card-header"><h3 class="card-title">Stock par depot</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead><tr><th>Depot</th><th class="text-right">Stock reel</th><th class="text-right">Reserve</th></tr></thead>
                        <tbody>
                        @forelse($article->stocks as $stock)
                            <tr><td>{{ $stock->depot?->intitule ?? '-' }}</td><td class="text-right">{{ number_format((float) $stock->stock_reel, 3, ',', ' ') }}</td><td class="text-right">{{ number_format((float) $stock->stock_reserve, 3, ',', ' ') }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">Aucun stock par depot.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card card-outline card-danger">
                <div class="card-header"><h3 class="card-title">Suivi stock (mouvements)</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead><tr><th>Date</th><th>Type</th><th>Depot</th><th>Qte</th></tr></thead>
                        <tbody>
                        @forelse($stockMovements as $mvt)
                            <tr><td>{{ optional($mvt->created_at)->format('Y-m-d H:i') }}</td><td>{{ $mvt->movement_type ?: $mvt->type ?: '-' }}</td><td>{{ $mvt->depot?->intitule ?? '-' }}</td><td>{{ number_format((float) $mvt->quantity, 3, ',', ' ') }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Aucun mouvement stock.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="stock" role="tabpanel">
            <div class="row">
                <div class="col-lg-4 col-6"><div class="small-box {{ $article->ar_stock_actuel <= $article->ar_stock_min ? 'bg-warning' : 'bg-secondary' }}"><div class="inner"><h3>{{ number_format((float) $article->ar_stock_actuel, 3, ',', ' ') }}</h3><p>Stock actuel</p></div><div class="icon"><i class="fas fa-warehouse"></i></div></div></div>
                <div class="col-lg-4 col-6"><div class="small-box bg-dark"><div class="inner"><h3>{{ number_format((float) $article->ar_stock_min, 3, ',', ' ') }}</h3><p>Stock minimum</p></div><div class="icon"><i class="fas fa-exclamation-triangle"></i></div></div></div>
                <div class="col-lg-4 col-6"><div class="small-box bg-primary"><div class="inner"><h3>{{ $rotation90d ? number_format($rotation90d, 0, ',', ' ') : '-' }}</h3><p>Couverture (jours)</p></div><div class="icon"><i class="fas fa-stopwatch"></i></div></div></div>
            </div>
            <div class="row">
                <div class="col-md-5">
                    <div class="card card-outline card-primary">
                        <div class="card-header"><h3 class="card-title">Stock par depot</h3></div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-striped mb-0">
                                <thead><tr><th>Depot</th><th class="text-right">Stock reel</th><th class="text-right">Reserve</th></tr></thead>
                                <tbody>
                                @forelse($article->stocks as $stock)
                                    <tr><td>{{ $stock->depot?->intitule ?? '-' }}</td><td class="text-right">{{ number_format((float) $stock->stock_reel, 3, ',', ' ') }}</td><td class="text-right">{{ number_format((float) $stock->stock_reserve, 3, ',', ' ') }}</td></tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">Aucun stock par depot.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="card card-outline card-danger">
                        <div class="card-header"><h3 class="card-title">Suivi stock (mouvements)</h3></div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-sm table-striped mb-0">
                                <thead><tr><th>Date</th><th>Type</th><th>Depot</th><th>Qte</th></tr></thead>
                                <tbody>
                                @forelse($stockMovements as $mvt)
                                    <tr><td>{{ optional($mvt->created_at)->format('Y-m-d H:i') }}</td><td>{{ $mvt->movement_type ?: $mvt->type ?: '-' }}</td><td>{{ $mvt->depot?->intitule ?? '-' }}</td><td>{{ number_format((float) $mvt->quantity, 3, ',', ' ') }}</td></tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">Aucun mouvement stock.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="finance" role="tabpanel">
            <div class="row">
                <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3>{{ number_format($purchaseAmount, 2, ',', ' ') }}</h3><p>Total achats</p></div><div class="icon"><i class="fas fa-shopping-cart"></i></div></div></div>
                <div class="col-lg-3 col-6"><div class="small-box {{ $margin >= 0 ? 'bg-primary' : 'bg-danger' }}"><div class="inner"><h3>{{ number_format($margin, 2, ',', ' ') }}</h3><p>Marge brute</p></div><div class="icon"><i class="fas fa-coins"></i></div></div></div>
                <div class="col-lg-3 col-6"><div class="small-box bg-secondary"><div class="inner"><h3>{{ number_format($stockValorisation, 2, ',', ' ') }}</h3><p>Valorisation stock</p></div><div class="icon"><i class="fas fa-balance-scale"></i></div></div></div>
                <div class="col-lg-3 col-6"><div class="small-box bg-warning"><div class="inner"><h3>{{ number_format((float) $article->ar_tva, 2, ',', ' ') }}%</h3><p>TVA</p></div><div class="icon"><i class="fas fa-percentage"></i></div></div></div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="card card-outline card-success">
                        <div class="card-header"><h3 class="card-title">Top clients (article)</h3></div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-striped mb-0">
                                <thead><tr><th>Client</th><th class="text-right">CA</th></tr></thead>
                                <tbody>
                                @forelse($topClients as $c)
                                    <tr><td>{{ $c->name }}</td><td class="text-right">{{ number_format((float) $c->amount, 2, ',', ' ') }}</td></tr>
                                @empty
                                    <tr><td colspan="2" class="text-center text-muted">Pas de ventes client.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-outline card-info">
                        <div class="card-header"><h3 class="card-title">Top fournisseurs (article)</h3></div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-striped mb-0">
                                <thead><tr><th>Fournisseur</th><th class="text-right">Achat</th></tr></thead>
                                <tbody>
                                @forelse($topSuppliers as $s)
                                    <tr><td>{{ $s->name }}</td><td class="text-right">{{ number_format((float) $s->amount, 2, ',', ' ') }}</td></tr>
                                @empty
                                    <tr><td colspan="2" class="text-center text-muted">Pas d achats fournisseur.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
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
    new Chart(document.getElementById('articleTrendChart'), {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [
                { label: 'Ventes', data: chartData.sales, borderColor: '#28a745', backgroundColor: 'rgba(40,167,69,.12)', fill: true, tension: .35 },
                { label: 'Achats', data: chartData.purchases, borderColor: '#007bff', backgroundColor: 'rgba(0,123,255,.12)', fill: true, tension: .35 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
});
</script>
@endpush
