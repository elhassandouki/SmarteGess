@extends('adminlte::page')

@section('title', 'Familles')

@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)

@php
    $heads = [
        'Code',
        'Intitule',
        'Nb articles',
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
            <h1 class="m-0 text-dark">Familles</h1>
            <small class="text-muted">Organisation claire des familles produits.</small>
        </div>
        <a href="{{ route('families.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i> Nouvelle famille
        </a>
    </div>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="primary" theme-mode="outline" title="Filtres familles" icon="fas fa-filter">
        <form method="GET" action="{{ route('families.index') }}" class="row">
            <div class="col-md-8">
                <div class="form-group">
                    <label for="search">Recherche</label>
                    <input type="text" name="search" id="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Code ou intitule">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="min_articles">Min articles</label>
                    <input type="number" min="0" name="min_articles" id="min_articles" class="form-control" value="{{ $filters['min_articles'] }}">
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

    <x-adminlte-card theme="primary" theme-mode="outline" title="Liste des familles" icon="fas fa-layer-group">
        <div class="table-responsive">
            <table id="familiesTable" class="table table-striped table-hover table-bordered mb-0">
                <thead class="thead-dark"><tr><th>Code</th><th>Intitule</th><th>Nb articles</th><th>Actions</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </x-adminlte-card>
@stop

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if ($.fn.DataTable.isDataTable('#familiesTable')) $('#familiesTable').DataTable().destroy();
    const t = $('#familiesTable').DataTable({
        processing:true, serverSide:true,
        ajax:{ url:"{{ route('families.index') }}", data:d=>{ d.search=$('#search').val(); d.min_articles=$('#min_articles').val(); }},
        columns:[{data:'code'},{data:'intitule'},{data:'articles_count'},{data:'actions',orderable:false,searchable:false}]
    });
    $('#search,#min_articles').on('change keyup', function(){ t.ajax.reload(); });
});
</script>
@endpush

