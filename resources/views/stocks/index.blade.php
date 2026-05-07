@extends('adminlte::page')

@section('title', 'Stock')

@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)

@php
    $heads = [
        'Depot',
        'Code',
        'Article',
        'Famille',
        'Stock reel',
        'Reserve',
        'Seuil min',
        'Valorisation',
        ['label' => 'Ajustement', 'no-export' => true, 'width' => 24],
    ];

    $config = [
        'order' => [[0, 'asc']],
        'responsive' => true,
        'autoWidth' => false,
        'pageLength' => 10,
        'dom' => "<'row'<'col-md-6'B><'col-md-6'f>>" .
            "<'row'<'col-sm-12'tr>>" .
            "<'row mt-2'<'col-md-5'i><'col-md-7'p>>",
        'buttons' => ['copy', 'csv', 'excel', 'pdf', 'print', 'colvis'],
        'language' => ['url' => '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'],
    ];
@endphp

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="m-0 text-dark">Gestion du stock</h1>
            <small class="text-muted">Suivi par depot avec ajustement rapide.</small>
        </div>
    </div>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="info" theme-mode="outline" title="Filtres stock" icon="fas fa-filter">
        <form method="GET" action="{{ route('stocks.index') }}" class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="search">Recherche</label>
                    <input type="text" name="search" id="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Code ou designation article">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="depot_id">Depot</label>
                    <select name="depot_id" id="depot_id" class="form-control">
                        <option value="">Tous les depots</option>
                        @foreach ($depots as $depot)
                            <option value="{{ $depot->id }}" @selected($filters['depot_id'] == $depot->id)>{{ $depot->intitule }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-center">
                <div class="custom-control custom-switch mt-4">
                    <input type="checkbox" class="custom-control-input" id="low_only" name="low_only" value="1" @checked($filters['low_only'])>
                    <label class="custom-control-label" for="low_only">Afficher seulement le stock faible</label>
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end justify-content-end">
                <button type="submit" class="btn btn-info">Filtrer</button>
            </div>
        </form>
    </x-adminlte-card>

    <x-adminlte-card theme="primary" theme-mode="outline" title="Etat du stock" icon="fas fa-warehouse">
        <x-adminlte-datatable id="stocksTable" :heads="$heads" head-theme="light" striped hoverable bordered compressed with-buttons :config="$config">
            @foreach ($stocks as $stock)
                <tr>
                    <td>{{ $stock->depot?->intitule ?? '-' }}</td>
                    <td>{{ $stock->article?->code_article ?: $stock->article?->ar_ref ?: '-' }}</td>
                    <td>{{ $stock->article?->ar_design ?? '-' }}</td>
                    <td>{{ $stock->article?->family?->fa_intitule ?? '-' }}</td>
                    <td>
                        <span class="badge {{ (float) $stock->stock_reel <= (float) ($stock->article?->ar_stock_min ?? 0) ? 'badge-warning' : 'badge-light' }}">
                            {{ number_format((float) $stock->stock_reel, 3) }}
                        </span>
                    </td>
                    <td>{{ number_format((float) $stock->stock_reserve, 3) }}</td>
                    <td>{{ number_format((float) ($stock->article?->ar_stock_min ?? 0), 3) }}</td>
                    <td>{{ number_format((float) $stock->stock_reel * (float) ($stock->article?->ar_prix_achat ?? 0), 2) }}</td>
                    <td>
                        <form method="POST" action="{{ route('stocks.adjust', $stock) }}" class="form-inline justify-content-center">
                            @csrf
                            @method('PATCH')
                            <input type="number" step="0.001" min="0" name="stock_reel" value="{{ $stock->stock_reel }}" class="form-control form-control-sm mr-2" style="width: 92px;">
                            <input type="number" step="0.001" min="0" name="stock_reserve" value="{{ $stock->stock_reserve }}" class="form-control form-control-sm mr-2" style="width: 92px;">
                            <button type="submit" class="btn btn-xs btn-outline-primary">MAJ</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </x-adminlte-datatable>
    </x-adminlte-card>
@stop
