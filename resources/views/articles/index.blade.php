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
            </div>
        </form>
    </x-adminlte-card>

    <x-adminlte-card theme="success" theme-mode="outline" title="Liste des articles" icon="fas fa-boxes">
        <x-adminlte-datatable id="articlesTable" :heads="$heads" head-theme="light" striped hoverable bordered compressed with-buttons :config="$config">
            @foreach ($articles as $article)
                <tr>
                    <td>{{ $article->code_article ?: $article->ar_ref }}</td>
                    <td>{{ $article->ar_ref }}</td>
                    <td>{{ $article->ar_design }}</td>
                    <td>{{ $article->family?->fa_intitule ?? '-' }}</td>
                    <td>{{ number_format((float) $article->ar_prix_achat, 2) }}</td>
                    <td>{{ number_format((float) $article->ar_prix_vente, 2) }}</td>
                    <td>{{ number_format((float) $article->ar_tva, 2) }}%</td>
                    <td>
                        <span class="badge {{ (float) $article->ar_stock_actuel <= (float) $article->ar_stock_min ? 'badge-warning' : 'badge-light' }}">
                            {{ number_format((float) $article->ar_stock_actuel, 3) }}
                        </span>
                    </td>
                    <td>{{ number_format((float) $article->ar_stock_min, 3) }}</td>
                    <td>{{ $article->ar_unite }}</td>
                    <td>
                        <div class="d-flex justify-content-center">
                            <a href="{{ route('articles.edit', $article) }}" class="btn btn-xs btn-outline-primary mr-2">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form action="{{ route('articles.destroy', $article) }}" method="POST" onsubmit="return confirm('Supprimer cet article ?');">
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
