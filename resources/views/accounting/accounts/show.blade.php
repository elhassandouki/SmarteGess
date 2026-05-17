@extends('adminlte::page')

@section('title', $account->account_label)

@section('content_header')
    <h1 class="m-0 text-dark">Compte: {{ $account->account_code }} - {{ $account->account_label }}</h1>
@stop

@section('content')
    <x-adminlte-card theme="primary" theme-mode="outline" title="Details du compte" icon="fas fa-info-circle">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="font-weight-bold">Code Compte</label>
                    <p class="text-muted">{{ $account->account_code }}</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="font-weight-bold">Type</label>
                    <p class="text-muted">{{ $account->type_display }}</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="font-weight-bold">Statut</label>
                    <p class="text-muted">
                        @if($account->is_active)
                            <span class="badge badge-success">Actif</span>
                        @else
                            <span class="badge badge-secondary">Inactif</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
        <hr>
        <div class="form-group">
            <a href="{{ route('accounting.accounts.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Retour
            </a>
        </div>
    </x-adminlte-card>
@stop
