@extends('adminlte::page')

@section('title', 'Documents')

@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)

@php
    $moduleTitles = [
        'sales' => 'Documents ventes',
        'purchase' => 'Documents achats',
        'stock' => 'Documents stock & inventaire',
    ];

    $moduleTitle = $module ? ($moduleTitles[$module] ?? 'Documents') : 'Documents';

    $heads = [
        'Piece',
        'Date',
        'Type',
        'Tiers',
        'Transporteur',
        'Statut',
        'Paiement',
        'Lignes',
        'Total HT',
        ['label' => 'Actions', 'no-export' => true, 'width' => 20],
    ];

    $config = [
        'order' => [[1, 'desc']],
        'responsive' => true,
        'autoWidth' => false,
        'pageLength' => 15,
        'lengthChange' => true,
        'dom' => "<'row'<'col-md-6'B><'col-md-6'f>>" .
            "<'row'<'col-sm-12'tr>>" .
            "<'row mt-2'<'col-md-5'i><'col-md-7'p>>",
        'buttons' => ['copy', 'csv', 'excel', 'pdf', 'print', 'colvis'],
        'language' => ['url' => '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'],
    ];

    $statusThemes = [
        'livre' => 'success',
        'en_cours' => 'info',
        'en_attente' => 'secondary',
    ];
@endphp

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="m-0 text-dark">{{ $moduleTitle }}</h1>
            <small class="text-muted">Organisation ERP par module, avec actions rapides en modal.</small>
        </div>
        <a href="{{ route('documents.create', ['module' => $module]) }}" class="btn btn-warning">
            <i class="fas fa-plus mr-1"></i> Nouveau document
        </a>
    </div>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="warning" theme-mode="outline" title="Filtres documents" icon="fas fa-filter" collapsible>
        <div class="mb-3">
            <div class="btn-group btn-group-sm" role="group" aria-label="Vues ERP">
                <a href="{{ route('documents.sales') }}" class="btn btn-outline-secondary">Ventes</a>
                <a href="{{ route('documents.purchases') }}" class="btn btn-outline-secondary">Achats</a>
                <a href="{{ route('documents.stock') }}" class="btn btn-outline-secondary">Stock</a>
            </div>
        </div>
        <form method="GET" action="{{ route('documents.index') }}" class="row g-3">
            <input type="hidden" name="module" value="{{ $module }}">
            <div class="col-lg-2 col-md-4 col-12">
                <div class="form-group">
                    <label for="date_from">Du</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-12">
                <div class="form-group">
                    <label for="date_to">Au</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-12">
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
            <div class="col-lg-2 col-md-6 col-12">
                <div class="form-group">
                    <label for="type_document_code">Type</label>
                    <select name="type_document_code" id="type_document_code" class="form-control">
                        <option value="">Tous</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected($filters['type_document_code'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-lg-2 col-md-6 col-12">
                <div class="form-group">
                    <label for="payment_status">Paiement</label>
                    <select name="payment_status" id="payment_status" class="form-control">
                        <option value="">Tous</option>
                        @foreach ($statusMap as $value => $label)
                            <option value="{{ $value }}" @selected((string) $filters['payment_status'] === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-lg-1 col-md-6 col-12 d-flex align-items-end">
                <button type="submit" class="btn btn-warning btn-block">Filtrer</button>
            </div>
            <div class="col-lg-2 col-md-6 col-12 d-flex align-items-end">
                <button type="button" class="btn btn-outline-secondary btn-block js-reset-filters">Reinitialiser</button>
            </div>
        </form>
    </x-adminlte-card>

    <x-adminlte-card theme="warning" theme-mode="outline" title="Liste des documents" icon="fas fa-file-invoice">
        <div class="table-responsive">
            <table id="documentsTable" class="table table-striped table-hover table-bordered mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>Piece</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Tiers</th>
                        <th>Transporteur</th>
                        <th>Statut</th>
                        <th>Paiement</th>
                        <th>Lignes</th>
                        <th>Total HT</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-adminlte-card>

    <x-adminlte-modal id="quickViewModal" title="Detail rapide document" theme="dark" icon="fas fa-file-alt">
        <dl class="row mb-0">
            <dt class="col-5">Piece</dt><dd class="col-7" id="qvPiece">-</dd>
            <dt class="col-5">Date</dt><dd class="col-7" id="qvDate">-</dd>
            <dt class="col-5">Type</dt><dd class="col-7" id="qvType">-</dd>
            <dt class="col-5">Tiers</dt><dd class="col-7" id="qvTier">-</dd>
            <dt class="col-5">Lignes</dt><dd class="col-7" id="qvLines">-</dd>
            <dt class="col-5">Total TTC</dt><dd class="col-7 font-weight-bold" id="qvTotal">-</dd>
        </dl>
    </x-adminlte-modal>

    <x-adminlte-modal id="quickStatusModal" title="Mise a jour rapide statut" theme="success" icon="fas fa-shipping-fast">
        <form id="quickStatusForm" method="POST" data-ajax="true" data-modal-id="quickStatusModal">
            @csrf
            @method('PATCH')
            <div class="form-group">
                <label for="quick_status_select">Statut logistique</label>
                <select id="quick_status_select" name="do_expedition_statut" class="form-control">
                    <option value="en_attente">En attente</option>
                    <option value="en_cours">En cours</option>
                    <option value="livre">Livre</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success">Enregistrer</button>
        </form>
    </x-adminlte-modal>
@stop

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if ($.fn.DataTable.isDataTable('#documentsTable')) {
            $('#documentsTable').DataTable().destroy();
        }

        const documentsTable = $('#documentsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('documents.index') }}",
                type: "GET",
                data: function (d) {
                    d.module = "{{ $module }}";
                    d.date_from = $('#date_from').val();
                    d.date_to = $('#date_to').val();
                    d.tier_id = $('#tier_id').val();
                    d.type_document_code = $('#type_document_code').val();
                    d.payment_status = $('#payment_status').val();
                }
            },
            columns: [
                { data: 'piece', name: 'piece' },
                { data: 'date', name: 'date', orderable: true, searchable: false },
                { data: 'type', name: 'type' },
                { data: 'tier', name: 'tier' },
                { data: 'transporteur', name: 'transporteur' },
                { data: 'statut', name: 'statut', orderable: false, searchable: false },
                { data: 'paiement', name: 'paiement', orderable: false, searchable: false },
                { data: 'lignes', name: 'lignes', orderable: false, searchable: false },
                { data: 'total_ht', name: 'total_ht', orderable: true, searchable: false },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            order: [[1, 'desc']],
            responsive: true,
            autoWidth: false,
            pageLength: 15,
            language: {
                emptyTable: "Aucune donnee disponible",
                loadingRecords: "Chargement...",
                processing: "Traitement...",
                search: "Rechercher:",
                lengthMenu: "Afficher _MENU_ elements",
                info: "Affichage de _START_ a _END_ sur _TOTAL_ elements",
                infoEmpty: "Aucun element a afficher",
                paginate: {
                    previous: "Precedent",
                    next: "Suivant"
                }
            }
        });

        $('#date_from, #date_to, #tier_id, #type_document_code, #payment_status').on('change', function () {
            documentsTable.ajax.reload();
        });

        $(document).on('click', '.quick-view-btn', function () {
            const button = this;
            document.getElementById('qvPiece').textContent = button.dataset.piece || '-';
            document.getElementById('qvDate').textContent = button.dataset.date || '-';
            document.getElementById('qvType').textContent = button.dataset.type || '-';
            document.getElementById('qvTier').textContent = button.dataset.tier || '-';
            document.getElementById('qvLines').textContent = button.dataset.lines || '-';
            document.getElementById('qvTotal').textContent = button.dataset.total || '-';
        });

        $(document).on('click', '.quick-status-btn', function () {
            const button = this;
            if (button.dataset.action && button.dataset.current) {
                document.getElementById('quickStatusForm').setAttribute('action', button.dataset.action);
                document.getElementById('quick_status_select').value = button.dataset.current;
            }
        });
    });
</script>
@endpush

@include('partials.erp-interactions')

