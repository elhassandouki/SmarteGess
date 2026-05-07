@extends('adminlte::page')

@section('title', 'Detail document')

@section('plugins.Datatables', true)

@php
    $heads = ['Article', 'Qte', 'Prix U. HT', 'TVA', 'Montant HT', 'Montant TTC'];
    $paymentHeads = ['Date', 'Montant', 'Mode', 'Reference', 'Valide'];
    $stockHeads = ['Article', 'Type', 'Quantite', 'Date'];
    $config = [
        'paging' => false,
        'searching' => false,
        'info' => false,
        'responsive' => true,
        'autoWidth' => false,
    ];
@endphp

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="m-0 text-dark">Document {{ $document->do_piece }}</h1>
            <small class="text-muted">Vue detaillee du document.</small>
        </div>
        <div class="d-flex flex-wrap">
            <form action="{{ route('documents.duplicate', $document) }}" method="POST" class="mr-2">
                @csrf
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-copy mr-1"></i> Dupliquer
                </button>
            </form>
            <a href="{{ route('documents.edit', $document) }}" class="btn btn-primary">
                <i class="fas fa-pen mr-1"></i> Modifier
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-4">
            <x-adminlte-card theme="info" theme-mode="outline" title="Informations document">
                <div class="mb-2">
                    <span class="badge badge-info mr-1">{{ $types[$document->type_document_code ?: 'BC'] ?? 'N/A' }}</span>
                    <span class="badge badge-{{ $document->do_statut == 2 ? 'success' : ($document->do_statut == 1 ? 'warning' : 'secondary') }}">{{ $statusMap[$document->do_statut] ?? 'N/A' }}</span>
                </div>
                <p><strong>Pièce :</strong> {{ $document->do_piece }}</p>
                <p><strong>Date :</strong> {{ optional($document->do_date)->format('Y-m-d') }}</p>
                <p><strong>Tiers :</strong> {{ $document->tier?->code_tiers ?: $document->tier?->ct_num ?: '-' }} {{ $document->tier?->ct_intitule ? '- ' . $document->tier->ct_intitule : '' }}</p>
                <p><strong>Depot :</strong> {{ $document->depot?->intitule ?? '-' }}</p>
                <p><strong>Transporteur :</strong> {{ $document->transporteur?->tr_nom ?? '-' }}</p>
                <p class="mb-0"><strong>Lieu livraison :</strong> {{ $document->do_lieu_livraison ?: '-' }}</p>
            </x-adminlte-card>
        </div>

        <div class="col-md-4">
            <x-adminlte-card theme="olive" theme-mode="outline" title="Résumé financier">
                <p><strong>Total HT :</strong> {{ number_format((float) $document->do_total_ht, 2) }}</p>
                <p><strong>Total TVA :</strong> {{ number_format((float) $document->do_total_tva, 2) }}</p>
                <p><strong>Total TTC :</strong> <span class="h5">{{ number_format((float) $document->do_total_ttc, 2) }}</span></p>
                <hr>
                <p><strong>Montant réglé :</strong> {{ number_format((float) $document->do_montant_regle, 2) }}</p>
                <p><strong>Reste à payer :</strong> <span class="text-danger h6">{{ number_format((float) $document->do_total_ttc - $document->do_montant_regle, 2) }}</span></p>
                <p class="mb-0"><strong>Lignes :</strong> {{ $document->lines->count() }}</p>
            </x-adminlte-card>
        </div>

        <div class="col-md-4">
            <x-adminlte-card theme="purple" theme-mode="outline" title="Suivi opérationnel">
                <p><strong>Code tiers :</strong> {{ $document->tier?->code_tiers ?: $document->tier?->ct_num ?: '-' }}</p>
                <p><strong>Livraison prévue :</strong> {{ optional($document->do_date_livraison)->format('Y-m-d') ?: '-' }}</p>
                <p><strong>Etat paiement :</strong>
                    @if ($document->do_statut == 2)
                        <span class="badge badge-success">Réglé</span>
                    @elseif ($document->do_statut == 1)
                        <span class="badge badge-warning">Partiellement réglé</span>
                    @else
                        <span class="badge badge-danger">Non réglé</span>
                    @endif
                </p>
                <p><strong>Dernière mise à jour :</strong> {{ $document->updated_at->format('Y-m-d H:i') }}</p>
                <form action="{{ route('documents.update-status', $document) }}" method="POST" class="mt-3">
                    @csrf
                    @method('PATCH')
                    <div class="form-group">
                        <label for="do_expedition_statut" class="small text-muted">Changer le statut</label>
                        <select name="do_expedition_statut" id="do_expedition_statut" class="form-control">
                            <option value="en_attente" @selected($document->do_expedition_statut === 'en_attente')>En attente</option>
                            <option value="en_cours" @selected($document->do_expedition_statut === 'en_cours')>En cours</option>
                            <option value="livre" @selected($document->do_expedition_statut === 'livre')>Livré</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-outline-primary btn-block">Mettre à jour</button>
                </form>
            </x-adminlte-card>
        </div>
    </div>

    <x-adminlte-card theme="secondary" theme-mode="outline" title="Lignes du document" icon="fas fa-list">
        <x-adminlte-datatable id="documentLinesTable" :heads="$heads" head-theme="light" striped hoverable bordered compressed :config="$config">
            @foreach ($document->lines as $line)
                <tr>
                    <td>{{ $line->article?->code_article ?: $line->article?->ar_ref }} - {{ $line->article?->ar_design }}</td>
                    <td>{{ number_format((float) $line->dl_qte, 3) }}</td>
                    <td>{{ number_format((float) $line->dl_prix_unitaire_ht, 2) }}</td>
                    <td>{{ number_format((float) ($line->article?->ar_tva ?? 0), 2) }}%</td>
                    <td>{{ number_format((float) $line->dl_montant_ht, 2) }}</td>
                    <td>{{ number_format((float) $line->dl_montant_ttc, 2) }}</td>
                </tr>
            @endforeach
        </x-adminlte-datatable>
    </x-adminlte-card>

    {{-- Payment History --}}
    @if ($document->reglements->count() > 0)
        <x-adminlte-card theme="success" theme-mode="outline" title="Historique des paiements" icon="fas fa-money-bill">
            <x-adminlte-datatable id="paymentsTable" :heads="$paymentHeads" head-theme="light" striped hoverable bordered compressed :config="$config">
                @foreach ($document->reglements as $reglement)
                    <tr>
                        <td>{{ $reglement->rg_date->format('Y-m-d') }}</td>
                        <td><strong>{{ number_format((float) $reglement->rg_montant, 2) }}</strong></td>
                        <td>
                            @php
                                $modes = [1 => 'Especes', 2 => 'Cheque', 3 => 'Virement', 4 => 'Effet/Traite'];
                            @endphp
                            {{ $modes[$reglement->rg_mode_reglement] ?? 'N/A' }}
                        </td>
                        <td>{{ $reglement->rg_reference ?: '-' }}</td>
                        <td>
                            @if ($reglement->rg_valide)
                                <span class="badge badge-success">Oui</span>
                            @else
                                <span class="badge badge-warning">En attente</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-adminlte-datatable>
        </x-adminlte-card>
    @endif

    {{-- Stock Movements --}}
    @if ($stockMovements->count() > 0)
        <x-adminlte-card theme="info" theme-mode="outline" title="Mouvements de stock" icon="fas fa-exchange-alt">
            <x-adminlte-datatable id="stockMovementsTable" :heads="$stockHeads" head-theme="light" striped hoverable bordered compressed :config="$config">
                @foreach ($stockMovements as $movement)
                    <tr>
                        <td>{{ $movement->article?->code_article ?: $movement->article?->ar_ref }} - {{ $movement->article?->ar_design }}</td>
                        <td>
                            @if ($movement->movement_type === 'IN')
                                <span class="badge badge-success">Entree</span>
                            @elseif ($movement->movement_type === 'OUT')
                                <span class="badge badge-danger">Sortie</span>
                            @else
                                <span class="badge badge-secondary">{{ $movement->movement_type }}</span>
                            @endif
                        </td>
                        <td>{{ number_format((float) $movement->quantity, 3) }}</td>
                        <td>{{ $movement->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @endforeach
            </x-adminlte-datatable>
        </x-adminlte-card>
    @endif
@stop
