@extends('adminlte::page')

@section('title', 'Taxes')

@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)

@php
    $heads = [
        'Code Taxe',
        'Libelle',
        'Taux (%)',
        ['label' => 'Actions', 'no-export' => true, 'width' => 18],
    ];

    $config = [
        'order' => [[0, 'asc']],
        'responsive' => true,
        'autoWidth' => false,
        'language' => ['url' => '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'],
    ];
@endphp

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="m-0 text-dark">Gestion des Taxes</h1>
            <small class="text-muted">Configurer les taux de taxe et TVA pour vos articles.</small>
        </div>
        @can('taxes.create')
            <a href="{{ route('taxes.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> Nouvelle taxe
            </a>
        @endcan
    </div>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="primary" theme-mode="outline" title="Filtres" icon="fas fa-filter">
        <form method="GET" action="{{ route('taxes.index') }}" class="row">
            <div class="col-md-8">
                <div class="form-group">
                    <label for="search">Recherche</label>
                    <input type="text" name="search" id="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Code ou libelle">
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

    <x-adminlte-card theme="primary" theme-mode="outline" title="Liste des taxes" icon="fas fa-percentage">
        <div class="table-responsive">
            <table id="taxesTable" class="table table-striped table-hover table-bordered mb-0">
                <thead class="thead-dark"><tr><th>Code Taxe</th><th>Libelle</th><th>Taux (%)</th><th>Actions</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </x-adminlte-card>
@stop

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if ($.fn.DataTable.isDataTable('#taxesTable')) $('#taxesTable').DataTable().destroy();
    const t = $('#taxesTable').DataTable({
        processing:true, serverSide:true,
        ajax:{ url:"{{ route('taxes.index') }}", data:d=>{ d.search=$('#search').val(); }},
        columns:[{data:'code_taxe'},{data:'libelle'},{data:'taux'},{data:'actions',orderable:false,searchable:false}]
    });

    document.querySelector('.js-reset-filters')?.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('search').value = '';
        document.querySelector('form').submit();
    });
});
</script>
@endpush
