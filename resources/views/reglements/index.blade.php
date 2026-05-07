@extends('adminlte::page')

@section('title', 'Reglements')

@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)

@php
    $heads = [
        'Date',
        'Tiers',
        'Document',
        'Libelle',
        'Mode',
        'Montant',
        'Valide',
        ['label' => 'Actions', 'no-export' => true, 'width' => 14],
    ];

    $config = [
        'order' => [[0, 'desc']],
        'responsive' => true,
        'autoWidth' => false,
        'pageLength' => 10,
        'dom' => "<'row'<'col-md-6'B><'col-md-6'f>>" .
            "<'row'<'col-sm-12'tr>>" .
            "<'row mt-2'<'col-md-5'i><'col-md-7'p>>",
        'buttons' => ['copy', 'csv', 'excel', 'pdf', 'print', 'colvis'],
        'language' => ['url' => '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'],
    ];
@endphp

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="m-0 text-dark">Reglements</h1>
            <small class="text-muted">Paiements clients et fournisseurs lies aux documents.</small>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#quickReglementModal">
                <i class="fas fa-plus mr-1"></i> Nouveau reglement
            </button>
            <a href="{{ route('reglements.create') }}" class="btn btn-outline-success">Formulaire complet</a>
        </div>
    </div>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="success" theme-mode="outline" title="Filtres reglements" icon="fas fa-filter">
        <form method="GET" action="{{ route('reglements.index') }}" class="row">
            <div class="col-md-2">
                <div class="form-group">
                    <label for="date_from">Du</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="date_to">Au</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="tier_id">Tiers</label>
                    <select name="tier_id" id="tier_id" class="form-control">
                        <option value="">Tous</option>
                        @foreach ($tiers as $tier)
                            <option value="{{ $tier->id }}" @selected($filters['tier_id'] == $tier->id)>{{ $tier->code_tiers ?: $tier->ct_num }} - {{ $tier->ct_intitule }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="mode">Mode</label>
                    <select name="mode" id="mode" class="form-control">
                        <option value="">Tous</option>
                        @foreach ($modes as $value => $label)
                            <option value="{{ $value }}" @selected((string) $filters['mode'] === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="validated">Validation</label>
                    <select name="validated" id="validated" class="form-control">
                        <option value="">Tous</option>
                        <option value="1" @selected($filters['validated'] === '1')>Valide</option>
                        <option value="0" @selected($filters['validated'] === '0')>Non valide</option>
                    </select>
                </div>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-success w-100">OK</button>
            </div>
        </form>
    </x-adminlte-card>

    <x-adminlte-card theme="success" theme-mode="outline" title="Journal des reglements" icon="fas fa-cash-register">
        <x-adminlte-datatable id="reglementsTable" :heads="$heads" head-theme="light" striped hoverable bordered compressed with-buttons :config="$config">
            @foreach ($reglements as $reglement)
                <tr>
                    <td>{{ optional($reglement->rg_date)->format('Y-m-d') }}</td>
                    <td>{{ $reglement->tier?->code_tiers ?: $reglement->tier?->ct_num ?: '-' }}</td>
                    <td>{{ $reglement->document?->do_piece ?? '-' }}</td>
                    <td>{{ $reglement->rg_libelle ?: '-' }}</td>
                    <td>{{ $modes[$reglement->rg_mode_reglement] ?? 'N/A' }}</td>
                    <td>{{ number_format((float) $reglement->rg_montant, 2) }}</td>
                    <td>
                        <span class="badge badge-{{ $reglement->rg_valide ? 'success' : 'secondary' }}">
                            {{ $reglement->rg_valide ? 'Oui' : 'Non' }}
                        </span>
                    </td>
                    <td>
                        <form action="{{ route('reglements.destroy', $reglement) }}" method="POST" data-ajax-delete="true" data-confirm="Supprimer ce reglement ?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-outline-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </x-adminlte-datatable>
    </x-adminlte-card>

    <x-adminlte-modal id="quickReglementModal" title="Saisie rapide reglement" theme="success" icon="fas fa-money-check-alt">
        <form method="POST" action="{{ route('reglements.store') }}" data-ajax="true" data-modal-id="quickReglementModal">
            @csrf
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Tiers</label>
                    <select name="tier_id" class="form-control" required>
                        <option value="">Selectionner</option>
                        @foreach ($tiers as $tier)
                            <option value="{{ $tier->id }}">{{ $tier->code_tiers ?: $tier->ct_num }} - {{ $tier->ct_intitule }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>Mode</label>
                    <select name="rg_mode_reglement" class="form-control" required>
                        @foreach ($modes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4"><label>Date</label><input type="date" name="rg_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required></div>
                <div class="form-group col-md-4"><label>Montant</label><input type="number" step="0.01" min="0.01" name="rg_montant" class="form-control" required></div>
                <div class="form-group col-md-4"><label>Reference</label><input type="text" name="rg_reference" class="form-control"></div>
            </div>
            <div class="form-group">
                <label>Libelle</label>
                <input type="text" name="rg_libelle" class="form-control">
            </div>
            <div class="custom-control custom-switch mb-3">
                <input type="checkbox" name="rg_valide" id="quick_rg_valide" class="custom-control-input" value="1" checked>
                <label class="custom-control-label" for="quick_rg_valide">Reglement valide</label>
            </div>
            <button type="submit" class="btn btn-success">Enregistrer</button>
        </form>
    </x-adminlte-modal>
@stop

@include('partials.erp-interactions')
