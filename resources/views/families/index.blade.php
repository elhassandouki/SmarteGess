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
        </form>
    </x-adminlte-card>

    <x-adminlte-card theme="primary" theme-mode="outline" title="Liste des familles" icon="fas fa-layer-group">
        <x-adminlte-datatable id="familiesTable" :heads="$heads" head-theme="light" striped hoverable bordered compressed with-buttons :config="$config">
            @foreach ($families as $family)
                <tr>
                    <td>{{ $family->fa_code }}</td>
                    <td>{{ $family->fa_intitule }}</td>
                    <td>{{ $family->articles_count }}</td>
                    <td>
                        <div class="d-flex justify-content-center">
                            <a href="{{ route('families.edit', $family) }}" class="btn btn-xs btn-outline-primary mr-2">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form action="{{ route('families.destroy', $family) }}" method="POST" onsubmit="return confirm('Supprimer cette famille ?');">
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
