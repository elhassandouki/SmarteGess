@extends('adminlte::page')

@section('title', 'Tiers')

@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)

@php
    $entityTitles = [
        'clients' => 'Clients',
        'suppliers' => 'Fournisseurs',
    ];
    $entityTitle = $entityTitles[$entity ?? ''] ?? 'Tiers';

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
        'dom' => "<'row'<'col-md-6'B><'col-md-6'f>>" .
            "<'row'<'col-sm-12'tr>>" .
            "<'row mt-2'<'col-md-5'i><'col-md-7'p>>",
        'buttons' => ['copy', 'csv', 'excel', 'pdf', 'print', 'colvis'],
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
            <h1 class="m-0 text-dark">{{ $entityTitle }}</h1>
            <small class="text-muted">Gestion centralisee avec actions rapides en modal.</small>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#quickCreateTierModal">
                <i class="fas fa-plus mr-1"></i> Nouveau {{ strtolower($entityTitle === 'Tiers' ? 'tiers' : rtrim($entityTitle, 's')) }}
            </button>
            <a href="{{ route('tiers.create') }}" class="btn btn-outline-primary">Formulaire complet</a>
        </div>
    </div>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="primary" theme-mode="outline" title="Filtres tiers" icon="fas fa-filter">
        <form method="GET" action="{{ route('tiers.index') }}" class="row">
            @if (!empty($entity))
                <input type="hidden" name="entity" value="{{ $entity }}">
            @endif
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
        <div class="mt-2">
            <div class="btn-group btn-group-sm">
                <a href="{{ route('tiers.clients') }}" class="btn btn-outline-secondary">Clients</a>
                <a href="{{ route('tiers.suppliers') }}" class="btn btn-outline-secondary">Fournisseurs</a>
                <a href="{{ route('tiers.index') }}" class="btn btn-outline-secondary">Tous les tiers</a>
            </div>
        </div>
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
                            <button
                                type="button"
                                class="btn btn-xs btn-outline-info mr-2 quick-tier-view-btn"
                                data-toggle="modal"
                                data-target="#quickTierViewModal"
                                data-code="{{ $tier->code_tiers ?: $tier->ct_num }}"
                                data-intitule="{{ $tier->ct_intitule }}"
                                data-type="{{ ucfirst($tier->ct_type) }}"
                                data-telephone="{{ $tier->ct_telephone ?: '-' }}"
                                data-ice="{{ $tier->ct_ice ?: '-' }}"
                                data-adresse="{{ $tier->ct_adresse ?: '-' }}"
                                data-delai="{{ (int) $tier->ct_delai_paiement }}"
                                data-docs="{{ $tier->documents_count }}"
                            >
                                <i class="fas fa-eye"></i>
                            </button>
                            <button
                                type="button"
                                class="btn btn-xs btn-outline-warning mr-2 quick-tier-edit-btn"
                                data-toggle="modal"
                                data-target="#quickTierEditModal"
                                data-action="{{ route('tiers.update', $tier) }}"
                                data-ct_num="{{ $tier->ct_num }}"
                                data-code_tiers="{{ $tier->code_tiers }}"
                                data-ct_intitule="{{ $tier->ct_intitule }}"
                                data-ct_type="{{ $tier->ct_type }}"
                                data-ct_telephone="{{ $tier->ct_telephone }}"
                                data-ct_ice="{{ $tier->ct_ice }}"
                                data-ct_if="{{ $tier->ct_if }}"
                                data-ct_encours_max="{{ $tier->ct_encours_max }}"
                                data-ct_delai_paiement="{{ $tier->ct_delai_paiement }}"
                                data-ct_adresse="{{ $tier->ct_adresse }}"
                            >
                                <i class="fas fa-edit"></i>
                            </button>
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

    <x-adminlte-modal id="quickTierViewModal" title="Detail tiers" theme="info" icon="fas fa-address-card">
        <dl class="row mb-0">
            <dt class="col-4">Code</dt><dd class="col-8" id="qtvCode">-</dd>
            <dt class="col-4">Intitule</dt><dd class="col-8" id="qtvIntitule">-</dd>
            <dt class="col-4">Type</dt><dd class="col-8" id="qtvType">-</dd>
            <dt class="col-4">Telephone</dt><dd class="col-8" id="qtvTelephone">-</dd>
            <dt class="col-4">ICE</dt><dd class="col-8" id="qtvIce">-</dd>
            <dt class="col-4">Adresse</dt><dd class="col-8" id="qtvAdresse">-</dd>
            <dt class="col-4">Delai</dt><dd class="col-8" id="qtvDelai">-</dd>
            <dt class="col-4">Documents</dt><dd class="col-8" id="qtvDocs">-</dd>
        </dl>
    </x-adminlte-modal>

    <x-adminlte-modal id="quickCreateTierModal" title="Creation rapide tiers" theme="primary" icon="fas fa-user-plus">
        <form method="POST" action="{{ route('tiers.store') }}">
            @csrf
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Numero</label>
                    <input type="text" name="ct_num" class="form-control" required>
                </div>
                <div class="form-group col-md-4">
                    <label>Code</label>
                    <input type="text" name="code_tiers" class="form-control">
                </div>
                <div class="form-group col-md-4">
                    <label>Type</label>
                    <select name="ct_type" class="form-control">
                        <option value="client" @selected(($filters['type'] ?? '') === 'client')>Client</option>
                        <option value="fournisseur" @selected(($filters['type'] ?? '') === 'fournisseur')>Fournisseur</option>
                        <option value="prospect">Prospect</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Intitule</label>
                <input type="text" name="ct_intitule" class="form-control" required>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4"><label>Telephone</label><input type="text" name="ct_telephone" class="form-control"></div>
                <div class="form-group col-md-4"><label>ICE</label><input type="text" name="ct_ice" class="form-control"></div>
                <div class="form-group col-md-4"><label>Delai paiement</label><input type="number" name="ct_delai_paiement" min="0" class="form-control" value="0"></div>
            </div>
            <div class="form-group">
                <label>Adresse</label>
                <input type="text" name="ct_adresse" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Creer</button>
        </form>
    </x-adminlte-modal>

    <x-adminlte-modal id="quickTierEditModal" title="Edition rapide tiers" theme="warning" icon="fas fa-user-edit">
        <form id="quickTierEditForm" method="POST">
            @csrf
            @method('PUT')
            <div class="form-row">
                <div class="form-group col-md-4"><label>Numero</label><input type="text" name="ct_num" id="qe_ct_num" class="form-control" required></div>
                <div class="form-group col-md-4"><label>Code</label><input type="text" name="code_tiers" id="qe_code_tiers" class="form-control"></div>
                <div class="form-group col-md-4">
                    <label>Type</label>
                    <select name="ct_type" id="qe_ct_type" class="form-control">
                        <option value="client">Client</option>
                        <option value="fournisseur">Fournisseur</option>
                        <option value="prospect">Prospect</option>
                    </select>
                </div>
            </div>
            <div class="form-group"><label>Intitule</label><input type="text" name="ct_intitule" id="qe_ct_intitule" class="form-control" required></div>
            <div class="form-row">
                <div class="form-group col-md-4"><label>Telephone</label><input type="text" name="ct_telephone" id="qe_ct_telephone" class="form-control"></div>
                <div class="form-group col-md-4"><label>ICE</label><input type="text" name="ct_ice" id="qe_ct_ice" class="form-control"></div>
                <div class="form-group col-md-4"><label>IF</label><input type="text" name="ct_if" id="qe_ct_if" class="form-control"></div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6"><label>Encours max</label><input type="number" step="0.01" min="0" name="ct_encours_max" id="qe_ct_encours_max" class="form-control"></div>
                <div class="form-group col-md-6"><label>Delai paiement</label><input type="number" min="0" name="ct_delai_paiement" id="qe_ct_delai_paiement" class="form-control"></div>
            </div>
            <div class="form-group"><label>Adresse</label><input type="text" name="ct_adresse" id="qe_ct_adresse" class="form-control"></div>
            <button type="submit" class="btn btn-warning">Enregistrer</button>
        </form>
    </x-adminlte-modal>
@stop

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.quick-tier-view-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                document.getElementById('qtvCode').textContent = button.dataset.code || '-';
                document.getElementById('qtvIntitule').textContent = button.dataset.intitule || '-';
                document.getElementById('qtvType').textContent = button.dataset.type || '-';
                document.getElementById('qtvTelephone').textContent = button.dataset.telephone || '-';
                document.getElementById('qtvIce').textContent = button.dataset.ice || '-';
                document.getElementById('qtvAdresse').textContent = button.dataset.adresse || '-';
                document.getElementById('qtvDelai').textContent = (button.dataset.delai || '0') + ' j';
                document.getElementById('qtvDocs').textContent = button.dataset.docs || '0';
            });
        });

        document.querySelectorAll('.quick-tier-edit-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                document.getElementById('quickTierEditForm').setAttribute('action', button.dataset.action);
                document.getElementById('qe_ct_num').value = button.dataset.ct_num || '';
                document.getElementById('qe_code_tiers').value = button.dataset.code_tiers || '';
                document.getElementById('qe_ct_intitule').value = button.dataset.ct_intitule || '';
                document.getElementById('qe_ct_type').value = button.dataset.ct_type || 'client';
                document.getElementById('qe_ct_telephone').value = button.dataset.ct_telephone || '';
                document.getElementById('qe_ct_ice').value = button.dataset.ct_ice || '';
                document.getElementById('qe_ct_if').value = button.dataset.ct_if || '';
                document.getElementById('qe_ct_encours_max').value = button.dataset.ct_encours_max || 0;
                document.getElementById('qe_ct_delai_paiement').value = button.dataset.ct_delai_paiement || 0;
                document.getElementById('qe_ct_adresse').value = button.dataset.ct_adresse || '';
            });
        });
    });
</script>
@endpush
