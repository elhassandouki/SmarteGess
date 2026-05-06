@extends('adminlte::page')

@section('title', 'Dashboard')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="m-0">Dashboard</h1>
            <small class="text-muted">Pilotage rapide de SmartGess</small>
        </div>
        <a href="{{ route('documents.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i> Nouveau document
        </a>
    </div>
@stop

@section('content')
    @include('partials.flash')

    <div class="row">
        <div class="col-lg-4 col-12">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $stats['families'] }}</h3>
                    <p>Familles</p>
                </div>
                <div class="icon">
                    <i class="fas fa-layer-group"></i>
                </div>
                <a href="{{ route('families.index') }}" class="small-box-footer">Ouvrir <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>

        <div class="col-lg-4 col-12">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $stats['articles'] }}</h3>
                    <p>Articles</p>
                </div>
                <div class="icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <a href="{{ route('articles.index') }}" class="small-box-footer">Ouvrir <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>

        <div class="col-lg-4 col-12">
            <div class="small-box bg-indigo">
                <div class="inner">
                    <h3>{{ $stats['tiers'] }}</h3>
                    <p>Tiers</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <a href="{{ route('tiers.index') }}" class="small-box-footer">Ouvrir <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 col-12">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $stats['transporteurs'] }}</h3>
                    <p>Transporteurs</p>
                </div>
                <div class="icon">
                    <i class="fas fa-truck"></i>
                </div>
                <a href="{{ route('transporteurs.index') }}" class="small-box-footer">Ouvrir <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>

        <div class="col-lg-4 col-12">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $stats['documents'] }}</h3>
                    <p>Documents</p>
                </div>
                <div class="icon">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <a href="{{ route('documents.index') }}" class="small-box-footer">Ouvrir <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>

        <div class="col-lg-4 col-12">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $stats['stocks'] }} / {{ $stats['depots'] }}</h3>
                    <p>Stock / Depots</p>
                </div>
                <div class="icon">
                    <i class="fas fa-warehouse"></i>
                </div>
                <a href="{{ route('stocks.index') }}" class="small-box-footer">Ouvrir le stock <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">Documents recents</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0">
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
            <div class="card card-outline card-danger">
                <div class="card-header">
                    <h3 class="card-title">Stock sensible</h3>
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
