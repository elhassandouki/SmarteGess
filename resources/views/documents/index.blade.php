@extends('adminlte::page')

@section('title', 'Documents')

@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)

@php
    $heads = [
        'Piece',
        'Date',
        'Type',
        'Tiers',
        'Transporteur',
        'Statut',
        'Paiement',
        'Lignes',
        'Total HT',
        ['label' => 'Actions', 'no-export' => true, 'width' => 20],
    ];

    $config = [
        'order' => [[1, 'desc']],
        'responsive' => true,
        'autoWidth' => false,
        'pageLength' => 10,
        'language' => ['url' => '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'],
    ];

    $statusThemes = [
        'livre' => 'success',
        'en_cours' => 'info',
        'en_attente' => 'secondary',
    ];
@endphp

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="m-0 text-dark">Documents</h1>
            <small class="text-muted">Commandes, livraisons, factures et retours.</small>
        </div>
        <a href="{{ route('documents.create') }}" class="btn btn-warning">
            <i class="fas fa-plus mr-1"></i> Nouveau document
        </a>
    </div>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="warning" theme-mode="outline" title="Filtres documents" icon="fas fa-filter">
        <form method="GET" action="{{ route('documents.index') }}" class="row">
            <div class="col-md-2">
                <div class="form-group">
                    <label for="date_from">Du</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="date_to">Au</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="tier_id">Tiers</label>
                    <select name="tier_id" id="tier_id" class="form-control">
                        <option value="">Tous</option>
                        @foreach ($tiers as $tier)
                            <option value="{{ $tier->id }}" @selected($filters['tier_id'] == $tier->id)>{{ $tier->code_tiers ?: $tier->ct_num }} - {{ $tier->ct_intitule }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="type_document_code">Type</label>
                    <select name="type_document_code" id="type_document_code" class="form-control">
                        <option value="">Tous</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected($filters['type_document_code'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="payment_status">Paiement</label>
                    <select name="payment_status" id="payment_status" class="form-control">
                        <option value="">Tous</option>
                        @foreach ($statusMap as $value => $label)
                            <option value="{{ $value }}" @selected((string) $filters['payment_status'] === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-warning w-100">OK</button>
            </div>
        </form>
    </x-adminlte-card>

    <x-adminlte-card theme="warning" theme-mode="outline" title="Liste des documents" icon="fas fa-file-invoice">
        <x-adminlte-datatable id="documentsTable" :heads="$heads" head-theme="light" striped hoverable bordered compressed with-buttons :config="$config">
            @foreach ($documents as $document)
                <tr>
                    <td>{{ $document->do_piece }}</td>
                    <td>{{ optional($document->do_date)->format('Y-m-d') }}</td>
                    <td>{{ $types[$document->type_document_code ?: 'BC'] ?? 'N/A' }}</td>
                    <td>{{ $document->tier?->code_tiers ?: $document->tier?->ct_num ?: '-' }}</td>
                    <td>{{ $document->transporteur?->tr_nom ?? '-' }}</td>
                    <td>
                        <span class="badge badge-{{ $statusThemes[$document->do_expedition_statut] ?? 'secondary' }}">
                            {{ ucfirst(str_replace('_', ' ', $document->do_expedition_statut)) }}
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-{{ $document->do_statut == 2 ? 'success' : ($document->do_statut == 1 ? 'warning' : 'secondary') }}">
                            {{ $statusMap[$document->do_statut] ?? 'N/A' }}
                        </span>
                    </td>
                    <td>{{ $document->lines_count }}</td>
                    <td>{{ number_format((float) $document->do_total_ht, 2) }}</td>
                    <td>
                        <div class="d-flex justify-content-center flex-wrap">
                            <a href="{{ route('documents.show', $document) }}" class="btn btn-xs btn-outline-secondary mr-2">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('documents.edit', $document) }}" class="btn btn-xs btn-outline-primary mr-2">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form action="{{ route('documents.duplicate', $document) }}" method="POST" class="mr-2 mb-1">
                                @csrf
                                <button type="submit" class="btn btn-xs btn-outline-warning" title="Dupliquer">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </form>
                            <form action="{{ route('documents.update-status', $document) }}" method="POST" class="mr-2 mb-1">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="do_expedition_statut"
                                    value="{{ $document->do_expedition_statut === 'en_attente' ? 'en_cours' : 'livre' }}">
                                <button type="submit" class="btn btn-xs btn-outline-success"
                                    title="{{ $document->do_expedition_statut === 'en_attente' ? 'Passer en cours' : 'Marquer livre' }}">
                                    <i class="fas fa-shipping-fast"></i>
                                </button>
                            </form>
                            <a href="{{ route('reglements.create', ['doc_id' => $document->id, 'tier_id' => $document->tier_id]) }}" class="btn btn-xs btn-outline-dark mr-2 mb-1" title="Reglement">
                                <i class="fas fa-cash-register"></i>
                            </a>
                            <form action="{{ route('documents.destroy', $document) }}" method="POST" onsubmit="return confirm('Supprimer ce document ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-outline-danger mb-1">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-adminlte-datatable>
    </x-adminlte-card>
@stop
