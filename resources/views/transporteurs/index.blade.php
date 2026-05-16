@extends('adminlte::page')

@section('title', 'Transporteurs')

@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)

@php
    $heads = [
        'Nom',
        'Matricule',
        'Chauffeur',
        'Telephone',
        'Documents',
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
            <h1 class="m-0 text-dark">Transporteurs</h1>
            <small class="text-muted">Suivi des partenaires de livraison.</small>
        </div>
        <a href="{{ route('transporteurs.create') }}" class="btn btn-info">
            <i class="fas fa-plus mr-1"></i> Nouveau transporteur
        </a>
    </div>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="info" theme-mode="outline" title="Filtres transporteurs" icon="fas fa-filter">
        <form method="GET" action="{{ route('transporteurs.index') }}" class="row">
            <div class="col-md-10">
                <div class="form-group">
                    <label for="search">Recherche</label>
                    <input type="text" name="search" id="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Nom, matricule, chauffeur, telephone">
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-info w-100">Filtrer</button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-outline-secondary w-100 js-reset-filters">Reinitialiser</button>
            </div>
        </form>
    </x-adminlte-card>

    <x-adminlte-card theme="info" theme-mode="outline" title="Liste des transporteurs" icon="fas fa-truck">
        <div class="table-responsive">
            <table id="transporteursTable" class="table table-striped table-hover table-bordered mb-0">
                <thead class="thead-dark"><tr><th>Nom</th><th>Matricule</th><th>Chauffeur</th><th>Telephone</th><th>Documents</th><th>Actions</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </x-adminlte-card>
@stop

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if ($.fn.DataTable.isDataTable('#transporteursTable')) $('#transporteursTable').DataTable().destroy();
    const t = $('#transporteursTable').DataTable({
        processing:true, serverSide:true, ajax:{url:"{{ route('transporteurs.index') }}", data:d=>{ d.search=$('#search').val(); }},
        columns:[{data:'nom'},{data:'matricule'},{data:'chauffeur'},{data:'telephone'},{data:'documents'},{data:'actions',orderable:false,searchable:false}]
    });
    $('#search').on('change keyup', function(){ t.ajax.reload(); });
});
</script>
@endpush

