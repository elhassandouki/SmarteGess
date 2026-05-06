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
        </form>
    </x-adminlte-card>

    <x-adminlte-card theme="primary" theme-mode="outline" title="Liste des depots" icon="fas fa-warehouse">
        <x-adminlte-datatable id="depotsTable" :heads="$heads" head-theme="light" striped hoverable bordered compressed with-buttons :config="$config">
            @foreach ($depots as $depot)
                <tr>
                    <td>{{ $depot->code_depot }}</td>
                    <td>{{ $depot->intitule }}</td>
                    <td>{{ $depot->stocks_count }}</td>
                    <td>
                        <div class="d-flex justify-content-center">
                            <a href="{{ route('depots.edit', $depot) }}" class="btn btn-xs btn-outline-primary mr-2">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form action="{{ route('depots.destroy', $depot) }}" method="POST" onsubmit="return confirm('Supprimer ce depot ?');">
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
