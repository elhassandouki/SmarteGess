@extends('adminlte::page')

@section('title', 'Dashboard')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="m-0">Dashboard</h1>
            <small class="text-muted">Cockpit decisionnel: ventes, achats, paiements, stock et performance commerciale.</small>
        </div>
        <a href="{{ route('documents.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i> Nouveau document
        </a>
    </div>
@stop

@section('content')
    @include('partials.flash')

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ number_format((float) $stats['sales_total'], 0, ',', ' ') }}</h3>
                    <p>CA Ventes (TTC)</p>
                </div>
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <a href="{{ route('documents.sales') }}" class="small-box-footer">Voir ventes <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format((float) $stats['purchases_total'], 0, ',', ' ') }}</h3>
                    <p>Total Achats (TTC)</p>
                </div>
                <div class="icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <a href="{{ route('documents.purchases') }}" class="small-box-footer">Voir achats <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-indigo">
                <div class="inner">
                    <h3>{{ number_format((float) $stats['payments_total'], 0, ',', ' ') }}</h3>
                    <p>Paiements encaisses</p>
                </div>
                <div class="icon">
                    <i class="fas fa-money-check-alt"></i>
                </div>
                <a href="{{ route('reglements.index') }}" class="small-box-footer">Voir reglements <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number_format((float) $stats['receivable'], 0, ',', ' ') }}</h3>
                    <p>Reste a encaisser</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <a href="{{ route('documents.index') }}" class="small-box-footer">Suivre encours <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card card-outline card-primary">
                <div class="card-header"><h3 class="card-title">Tendance mensuelle (6 mois)</h3></div>
                <div class="card-body"><canvas id="trendChart" height="120"></canvas></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-outline card-info">
                <div class="card-header"><h3 class="card-title">Mix types documents</h3></div>
                <div class="card-body"><canvas id="mixChart" height="220"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card card-outline card-success">
                <div class="card-header"><h3 class="card-title">Top 5 Clients (CA)</h3></div>
                <div class="card-body">
                    <canvas id="clientsChart" height="180"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-outline card-warning">
                <div class="card-header"><h3 class="card-title">Top 5 Articles (Quantites)</h3></div>
                <div class="card-body">
                    <canvas id="articlesChart" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Documents recents</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0 js-auto-datatable">
                        <thead>
                            <tr>
                                <th>Piece</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Tiers</th>
                                <th>Transporteur</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentDocuments as $document)
                                <tr>
                                    <td><a href="{{ route('documents.show', $document) }}">{{ $document->do_piece }}</a></td>
                                    <td>{{ optional($document->do_date)->format('Y-m-d') }}</td>
                                    <td>{{ $documentTypes[$document->type_document_code ?: 'BC'] ?? 'N/A' }}</td>
                                    <td>{{ $document->tier?->code_tiers ?: $document->tier?->ct_num ?: '-' }}</td>
                                    <td>{{ $document->transporteur?->tr_nom ?? '-' }}</td>
                                    <td>{{ number_format((float) $document->do_total_ttc, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Aucun document pour le moment.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-outline card-primary mb-3">
                <div class="card-header">
                    <h3 class="card-title">CA par type de document</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                        <tr>
                            <th>Type</th>
                            <th class="text-right">CA</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($caByDocumentType as $row)
                            <tr>
                                <td>{{ $documentTypes[$row->type_document_code] ?? $row->type_document_code }}</td>
                                <td class="text-right">{{ number_format((float) $row->ca_total, 2, ',', ' ') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted">Aucune donnee</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card card-outline card-danger">
                <div class="card-header">
                    <h3 class="card-title">Alertes stock</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse ($lowStockArticles as $article)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $article->code_article ?: $article->ar_ref }}</strong>
                                    <div class="text-muted small">{{ $article->ar_design }}</div>
                                </div>
                                <span class="badge badge-warning">{{ number_format((float) $article->ar_stock_actuel, 3) }} / min {{ number_format((float) $article->ar_stock_min, 3) }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">Aucun article en stock faible.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="card card-outline card-success mt-3">
                <div class="card-header">
                    <h3 class="card-title">Derniers reglements</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse ($recentReglements as $reglement)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $reglement->tier?->code_tiers ?: $reglement->tier?->ct_num ?: '-' }}</strong>
                                    <div class="text-muted small">{{ $reglement->document?->do_piece ?? 'Sans document' }}</div>
                                </div>
                                <span class="badge badge-success">{{ number_format((float) $reglement->rg_montant, 2) }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">Aucun reglement pour le moment.</li>
                        @endforelse
                    </ul>
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
    const topClients = @json($topClients);
    const topArticles = @json($topArticles);

    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: chartData.monthly_labels,
            datasets: [
                { label: 'Ventes', data: chartData.sales_trend, borderColor: '#007bff', backgroundColor: 'rgba(0,123,255,.15)', tension: 0.35, fill: true },
                { label: 'Achats', data: chartData.purchases_trend, borderColor: '#28a745', backgroundColor: 'rgba(40,167,69,.12)', tension: 0.35, fill: true },
                { label: 'Paiements', data: chartData.payments_trend, borderColor: '#fd7e14', backgroundColor: 'rgba(253,126,20,.12)', tension: 0.35, fill: true },
            ]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    new Chart(document.getElementById('mixChart'), {
        type: 'doughnut',
        data: {
            labels: chartData.document_mix_labels,
            datasets: [{ data: chartData.document_mix_values, backgroundColor: ['#007bff','#17a2b8','#28a745','#ffc107','#dc3545','#6f42c1'] }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    new Chart(document.getElementById('clientsChart'), {
        type: 'bar',
        data: {
            labels: topClients.map(c => c.name),
            datasets: [{ label: 'CA', data: topClients.map(c => Number(c.total)), backgroundColor: '#20c997' }]
        },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false }
    });

    new Chart(document.getElementById('articlesChart'), {
        type: 'bar',
        data: {
            labels: topArticles.map(a => a.name),
            datasets: [{ label: 'Qte', data: topArticles.map(a => Number(a.qty)), backgroundColor: '#ffc107' }]
        },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false }
    });
});
</script>
@endpush
