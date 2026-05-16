@extends('adminlte::page')

@section('title', 'Depots')

@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)

@php
    $heads = [
        'Code',
        'Intitule',
        'Lignes stock',
        ['label' => 'Actions', 'no-export' => true, 'width' => 18],
    ];

    $config = [
        'order' => [[1, 'asc']],
        'responsive' => true,
        'autoWidth' => false,
        'language' => ['url' => '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'],
    ];
@endphp

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="m-0 text-dark">Depots</h1>
            <small class="text-muted">Creation et organisation des emplacements de stock.</small>
        </div>
        <a href="{{ route('depots.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i> Nouveau depot
        </a>
    </div>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="primary" theme-mode="outline" title="Filtres depots" icon="fas fa-filter">
        <form method="GET" action="{{ route('depots.index') }}" class="row">
            <div class="col-md-10">
                <div class="form-group">
                    <label for="search">Recherche</label>
                    <input type="text" name="search" id="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Code ou intitule">
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filtrer</button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-outline-secondary w-100 js-reset-filters">Reinitialiser</button>
            </div>
        </form>
    </x-adminlte-card>

    <x-adminlte-card theme="primary" theme-mode="outline" title="Liste des depots" icon="fas fa-warehouse">
        <div class="table-responsive">
            <table id="depotsTable" class="table table-striped table-hover table-bordered mb-0">
                <thead class="thead-dark"><tr><th>Code</th><th>Intitule</th><th>Lignes stock</th><th>Actions</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </x-adminlte-card>
@stop

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if ($.fn.DataTable.isDataTable('#depotsTable')) $('#depotsTable').DataTable().destroy();
    const t = $('#depotsTable').DataTable({
        processing:true, serverSide:true,
        ajax:{ url:"{{ route('depots.index') }}", data:d=>{ d.search=$('#search').val(); }},
        columns:[{data:'code'},{data:'intitule'},{data:'stocks_count'},{data:'actions',orderable:false,searchable:false}]
    });
    $('#search').on('change keyup', function(){ t.ajax.reload(); });
});
</script>
@endpush

