@extends('adminlte::page')

@section('title', 'Tiers')

@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)

@php
    $heads = [
        'Numero',
        'Code',
        'Intitule',
        'Type',
        'Telephone',
        'ICE',
        'Delai',
        'Documents',
        ['label' => 'Actions', 'no-export' => true, 'width' => 18],
    ];

    $config = [
        'order' => [[2, 'asc']],
        'responsive' => true,
        'autoWidth' => false,
        'pageLength' => 10,
        'language' => ['url' => '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'],
    ];

    $typeThemes = [
        'client' => 'success',
        'fournisseur' => 'info',
        'prospect' => 'warning',
    ];
@endphp

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="m-0 text-dark">Tiers</h1>
            <small class="text-muted">Clients, fournisseurs et prospects centralises.</small>
        </div>
        <a href="{{ route('tiers.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i> Nouveau tiers
        </a>
    </div>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="primary" theme-mode="outline" title="Filtres tiers" icon="fas fa-filter">
        <form method="GET" action="{{ route('tiers.index') }}" class="row">
            <div class="col-md-8">
                <div class="form-group">
                    <label for="search">Recherche</label>
                    <input type="text" name="search" id="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Code, intitule, telephone, ICE...">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="type">Type</label>
                    <select name="type" id="type" class="form-control">
                        <option value="">Tous</option>
                        <option value="client" @selected($filters['type'] === 'client')>Client</option>
                        <option value="fournisseur" @selected($filters['type'] === 'fournisseur')>Fournisseur</option>
                        <option value="prospect" @selected($filters['type'] === 'prospect')>Prospect</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filtrer</button>
            </div>
        </form>
    </x-adminlte-card>

    <x-adminlte-card theme="primary" theme-mode="outline" title="Base tiers" icon="fas fa-users">
        <x-adminlte-datatable id="tiersTable" :heads="$heads" head-theme="light" striped hoverable bordered compressed with-buttons :config="$config">
            @foreach ($tiers as $tier)
                <tr>
                    <td>{{ $tier->ct_num }}</td>
                    <td>{{ $tier->code_tiers ?: '-' }}</td>
                    <td>
                        <div class="font-weight-bold">{{ $tier->ct_intitule }}</div>
                        <small class="text-muted">{{ $tier->ct_adresse ?: 'Adresse non renseignee' }}</small>
                    </td>
                    <td>
                        <span class="badge badge-{{ $typeThemes[$tier->ct_type] ?? 'secondary' }}">
                            {{ ucfirst($tier->ct_type) }}
                        </span>
                    </td>
                    <td>{{ $tier->ct_telephone ?: '-' }}</td>
                    <td>{{ $tier->ct_ice ?: '-' }}</td>
                    <td>{{ (int) $tier->ct_delai_paiement }} j</td>
                    <td>{{ $tier->documents_count }}</td>
                    <td>
                        <div class="d-flex justify-content-center">
                            <a href="{{ route('tiers.edit', $tier) }}" class="btn btn-xs btn-outline-primary mr-2">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form action="{{ route('tiers.destroy', $tier) }}" method="POST" onsubmit="return confirm('Supprimer ce tiers ?');">
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
