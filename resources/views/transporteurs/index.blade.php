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
        </form>
    </x-adminlte-card>

    <x-adminlte-card theme="info" theme-mode="outline" title="Liste des transporteurs" icon="fas fa-truck">
        <x-adminlte-datatable id="transporteursTable" :heads="$heads" head-theme="light" striped hoverable bordered compressed with-buttons :config="$config">
            @foreach ($transporteurs as $transporteur)
                <tr>
                    <td>{{ $transporteur->tr_nom }}</td>
                    <td>{{ $transporteur->tr_matricule ?: '-' }}</td>
                    <td>{{ $transporteur->tr_chauffeur ?: '-' }}</td>
                    <td>{{ $transporteur->tr_telephone ?: '-' }}</td>
                    <td>{{ $transporteur->documents_count }}</td>
                    <td>
                        <div class="d-flex justify-content-center">
                            <a href="{{ route('transporteurs.edit', $transporteur) }}" class="btn btn-xs btn-outline-primary mr-2">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form action="{{ route('transporteurs.destroy', $transporteur) }}" method="POST" onsubmit="return confirm('Supprimer ce transporteur ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-outline-danger">
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
