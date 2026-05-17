@extends('adminlte::page')

@section('title', 'Entree comptable du ' . $entry->entry_date?->format('d/m/Y'))

@section('content_header')
    <h1 class="m-0 text-dark">Entree comptable #{{ $entry->id }}</h1>
@stop

@section('content')
    <x-adminlte-card theme="primary" theme-mode="outline" title="Informations generales" icon="fas fa-info-circle">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label class="font-weight-bold">Date</label>
                    <p class="text-muted">{{ $entry->entry_date?->format('d/m/Y') }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="font-weight-bold">Journal</label>
                    <p class="text-muted">{{ $entry->journal_code }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="font-weight-bold">Reference</label>
                    <p class="text-muted">{{ $entry->reference_number ?? '-' }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="font-weight-bold">Statut</label>
                    <p class="text-muted">
                        @if($entry->status === 'draft')
                            <span class="badge badge-info">Brouillon</span>
                        @elseif($entry->status === 'posted')
                            <span class="badge badge-success">Validee</span>
                        @else
                            <span class="badge badge-danger">Annulee</span>
                        @endif
                    </p>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label class="font-weight-bold">Libelle</label>
                    <p class="text-muted">{{ $entry->label }}</p>
                </div>
            </div>
        </div>
    </x-adminlte-card>

    <x-adminlte-card theme="primary" theme-mode="outline" title="Lignes d'entree" icon="fas fa-list">
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Code Compte</th>
                        <th>Libelle</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Credit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entry->lines as $line)
                        <tr>
                            <td>{{ $line->account_code }}</td>
                            <td>{{ $line->account_label }}</td>
                            <td class="text-right">{{ number_format($line->debit, 2, ',', ' ') }}</td>
                            <td class="text-right">{{ number_format($line->credit, 2, ',', ' ') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">Aucune ligne</td>
                        </tr>
                    @endforelse
                    <tr class="font-weight-bold table-light">
                        <td colspan="2">Total</td>
                        <td class="text-right">{{ number_format($entry->debit_total, 2, ',', ' ') }}</td>
                        <td class="text-right">{{ number_format($entry->credit_total, 2, ',', ' ') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-adminlte-card>

    <x-adminlte-card theme="primary" theme-mode="outline" icon="fas fa-arrow-left">
        <a href="{{ route('accounting.entries.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Retour
        </a>
    </x-adminlte-card>
@stop
