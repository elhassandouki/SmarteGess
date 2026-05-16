@extends('adminlte::page')

@section('title', 'Articles')

@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)

@php
    $heads = [
        'Code',
        'Reference',
        'Designation',
        'Famille',
        'Prix achat',
        'Prix vente',
        'TVA',
        'Stock',
        'Stock min',
        'Unite',
        ['label' => 'Actions', 'no-export' => true, 'width' => 18],
    ];

    $config = [
        'order' => [[2, 'asc']],
        'responsive' => true,
        'autoWidth' => false,
        'pageLength' => 10,
        'language' => ['url' => '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'],
    ];
@endphp

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="m-0 text-dark">Articles</h1>
            <small class="text-muted">Catalogue commercial avec suivi du stock.</small>
        </div>
        <a href="{{ route('articles.create') }}" class="btn btn-success">
            <i class="fas fa-plus mr-1"></i> Nouvel article
        </a>
    </div>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="success" theme-mode="outline" title="Filtres articles" icon="fas fa-filter">
        <form method="GET" action="{{ route('articles.index') }}" class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="search">Recherche</label>
                    <input type="text" name="search" id="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Code, reference, designation ou code-barres">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="family_id">Famille</label>
                    <select name="family_id" id="family_id" class="form-control">
                        <option value="">Toutes</option>
                        @foreach ($families as $family)
                            <option value="{{ $family->id }}" @selected($filters['family_id'] == $family->id)>{{ $family->fa_intitule }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-2 d-flex flex-column justify-content-end">
                <div class="custom-control custom-switch mb-2">
                    <input type="checkbox" class="custom-control-input" id="low_only" name="low_only" value="1" @checked($filters['low_only'])>
                    <label class="custom-control-label" for="low_only">Stock faible</label>
                </div>
                <button type="submit" class="btn btn-success">Filtrer</button>
                <button type="button" class="btn btn-outline-secondary mt-2 js-reset-filters">Reinitialiser</button>
            </div>
        </form>
    </x-adminlte-card>

    <x-adminlte-card theme="success" theme-mode="outline" title="Liste des articles" icon="fas fa-boxes">
        <div class="table-responsive">
            <table id="articlesTable" class="table table-striped table-hover table-bordered mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>Code</th><th>Reference</th><th>Designation</th><th>Famille</th>
                        <th>Prix achat</th><th>Prix vente</th><th>TVA</th><th>Stock</th>
                        <th>Stock min</th><th>Unite</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-adminlte-card>
@stop

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if ($.fn.DataTable.isDataTable('#articlesTable')) $('#articlesTable').DataTable().destroy();
    const t = $('#articlesTable').DataTable({
        processing: true, serverSide: true,
        ajax: { url: "{{ route('articles.index') }}", data: d => {
            d.search = $('#search').val(); d.family_id = $('#family_id').val(); d.low_only = $('#low_only').is(':checked') ? 1 : 0;
        }},
        columns: [
            {data:'code'},{data:'ref'},{data:'designation'},{data:'famille'},{data:'prix_achat'},{data:'prix_vente'},
            {data:'tva'},{data:'stock'},{data:'stock_min'},{data:'unite'},{data:'actions',orderable:false,searchable:false}
        ]
    });
    $('#search,#family_id,#low_only').on('change keyup', function(){ t.ajax.reload(); });
});
</script>
@endpush

