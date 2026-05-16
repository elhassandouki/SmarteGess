@extends('adminlte::page')

@section('title', 'Detail Tiers')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="m-0">{{ $tier->ct_intitule }}</h1>
            <small class="text-muted">{{ strtoupper($tier->ct_type) }} | {{ $tier->code_tiers ?: $tier->ct_num }}</small>
        </div>
        <div>
            <a href="{{ route('tiers.edit', $tier) }}" class="btn btn-primary"><i class="fas fa-pen mr-1"></i> Modifier</a>
            <a href="{{ route('tiers.index') }}" class="btn btn-default">Retour</a>
        </div>
    </div>
@stop

@section('content')
    @include('partials.flash')

    <ul class="nav nav-tabs" id="tierTabs" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#commercial">Vue commerciale</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#finance">Vue financiere</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#historique">Historique</a></li>
    </ul>

    <div class="tab-content pt-3">
        <div class="tab-pane fade show active" id="commercial">
            <div class="row">
                <div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3>{{ number_format($salesTotal, 2, ',', ' ') }}</h3><p>CA Ventes</p></div><div class="icon"><i class="fas fa-chart-line"></i></div></div></div>
                <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3>{{ number_format($purchaseTotal, 2, ',', ' ') }}</h3><p>Total Achats</p></div><div class="icon"><i class="fas fa-shopping-cart"></i></div></div></div>
                <div class="col-lg-3 col-6"><div class="small-box bg-primary"><div class="inner"><h3>{{ number_format($avgTicket, 2, ',', ' ') }}</h3><p>Ticket moyen vente</p></div><div class="icon"><i class="fas fa-receipt"></i></div></div></div>
                <div class="col-lg-3 col-6"><div class="small-box bg-secondary"><div class="inner"><h3>{{ $tier->documents_count }}</h3><p>Documents</p></div><div class="icon"><i class="fas fa-file-invoice"></i></div></div></div>
            </div>

            <div class="card card-outline card-primary">
                <div class="card-header"><h3 class="card-title">Evolution mensuelle (6 mois)</h3></div>
                <div class="card-body"><canvas id="tierTrendChart" height="110"></canvas></div>
            </div>

            <div class="card card-outline card-success">
                <div class="card-header"><h3 class="card-title">Top articles (tiers)</h3></div>
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

        <div class="tab-pane fade" id="finance">
            <div class="row">
                <div class="col-lg-3 col-6"><div class="small-box bg-warning"><div class="inner"><h3>{{ number_format($paidOnDocs, 2, ',', ' ') }}</h3><p>Regle sur docs</p></div><div class="icon"><i class="fas fa-money-check"></i></div></div></div>
                <div class="col-lg-3 col-6"><div class="small-box bg-indigo"><div class="inner"><h3>{{ number_format($reglementsTotal, 2, ',', ' ') }}</h3><p>Reglements saisis</p></div><div class="icon"><i class="fas fa-cash-register"></i></div></div></div>
                <div class="col-lg-3 col-6"><div class="small-box {{ $encours > 0 ? 'bg-danger' : 'bg-success' }}"><div class="inner"><h3>{{ number_format($encours, 2, ',', ' ') }}</h3><p>Encours client</p></div><div class="icon"><i class="fas fa-exclamation-circle"></i></div></div></div>
                <div class="col-lg-3 col-6"><div class="small-box bg-dark"><div class="inner"><h3>{{ number_format((float) $tier->ct_encours_max, 2, ',', ' ') }}</h3><p>Plafond encours</p></div><div class="icon"><i class="fas fa-shield-alt"></i></div></div></div>
            </div>
            <div class="card card-outline card-secondary">
                <div class="card-header"><h3 class="card-title">Informations paiement</h3></div>
                <div class="card-body">
                    <p class="mb-1"><strong>Delai paiement:</strong> {{ (int) $tier->ct_delai_paiement }} jours</p>
                    <p class="mb-1"><strong>Derniere vente:</strong> {{ $lastSaleDate ? \Carbon\Carbon::parse($lastSaleDate)->format('Y-m-d') : '-' }}</p>
                    <p class="mb-1"><strong>Dernier achat:</strong> {{ $lastPurchaseDate ? \Carbon\Carbon::parse($lastPurchaseDate)->format('Y-m-d') : '-' }}</p>
                    <p class="mb-0"><strong>Telephone:</strong> {{ $tier->ct_telephone ?: '-' }}</p>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="historique">
            <div class="card card-outline card-warning">
                <div class="card-header"><h3 class="card-title">Historique documents</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>Date</th><th>Piece</th><th>Module</th><th>Type</th><th class="text-right">TTC</th><th>Statut</th></tr></thead>
                        <tbody>
                        @forelse($recentDocuments as $doc)
                            <tr>
                                <td>{{ optional($doc->do_date)->format('Y-m-d') }}</td>
                                <td><a href="{{ route('documents.show', $doc) }}">{{ $doc->do_piece }}</a></td>
                                <td>{{ strtoupper((string) $doc->doc_module) }}</td>
                                <td>{{ $doc->type_document_code ?: '-' }}</td>
                                <td class="text-right">{{ number_format((float) $doc->do_total_ttc, 2, ',', ' ') }}</td>
                                <td>{{ (int) $doc->do_statut === 2 ? 'Regle' : ((int) $doc->do_statut === 1 ? 'Partiel' : 'Non regle') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">Aucun document.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card card-outline card-info">
                <div class="card-header"><h3 class="card-title">Historique reglements</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead><tr><th>Date</th><th>Document</th><th>Mode</th><th class="text-right">Montant</th><th>Valide</th></tr></thead>
                        <tbody>
                        @forelse($recentReglements as $rg)
                            <tr>
                                <td>{{ optional($rg->rg_date)->format('Y-m-d') }}</td>
                                <td>{{ $rg->document?->do_piece ?? '-' }}</td>
                                <td>{{ (int) $rg->rg_mode_reglement }}</td>
                                <td class="text-right">{{ number_format((float) $rg->rg_montant, 2, ',', ' ') }}</td>
                                <td>{{ $rg->rg_valide ? 'Oui' : 'Non' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Aucun reglement.</td></tr>
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
    new Chart(document.getElementById('tierTrendChart'), {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [
                { label: 'Ventes', data: chartData.sales, borderColor: '#28a745', backgroundColor: 'rgba(40,167,69,.10)', fill: true, tension: .35 },
                { label: 'Achats', data: chartData.purchases, borderColor: '#007bff', backgroundColor: 'rgba(0,123,255,.10)', fill: true, tension: .35 },
                { label: 'Paiements', data: chartData.payments, borderColor: '#fd7e14', backgroundColor: 'rgba(253,126,20,.10)', fill: true, tension: .35 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
});
</script>
@endpush

