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
            <div class="col-md-2 d-flex align-items-end justify-content-end">
                <button type="button" class="btn btn-outline-secondary js-reset-filters">Reinitialiser</button>
            </div>
        </form>
    </x-adminlte-card>

    <x-adminlte-card theme="primary" theme-mode="outline" title="Etat du stock" icon="fas fa-warehouse">
        <div class="table-responsive">
            <table id="stocksTable" class="table table-striped table-hover table-bordered mb-0">
                <thead class="thead-dark"><tr><th>Depot</th><th>Code</th><th>Article</th><th>Famille</th><th>Stock reel</th><th>Reserve</th><th>Seuil min</th><th>Valorisation</th><th>Ajustement</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </x-adminlte-card>
@stop

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if ($.fn.DataTable.isDataTable('#stocksTable')) $('#stocksTable').DataTable().destroy();
    const t = $('#stocksTable').DataTable({
        processing:true, serverSide:true, ajax:{url:"{{ route('stocks.index') }}", data:d=>{ d.search=$('#search').val(); d.depot_id=$('#depot_id').val(); d.low_only=$('#low_only').is(':checked')?1:0; }},
        columns:[{data:'depot'},{data:'code'},{data:'article'},{data:'famille'},{data:'stock_reel'},{data:'stock_reserve'},{data:'stock_min'},{data:'valorisation'},{data:'ajustement',orderable:false,searchable:false}]
    });
    $('#search,#depot_id,#low_only').on('change keyup', function(){ t.ajax.reload(); });
});
</script>
@endpush

@include('partials.erp-interactions')

