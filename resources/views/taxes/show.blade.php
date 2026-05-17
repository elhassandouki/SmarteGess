@extends('adminlte::page')

@section('title', $tax->libelle)

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <h1 class="m-0 text-dark">{{ $tax->libelle }}</h1>
        @can('taxes.update')
            <a href="{{ route('taxes.edit', $tax) }}" class="btn btn-primary">
                <i class="fas fa-pen mr-1"></i> Modifier
            </a>
        @endcan
    </div>
@stop

@section('content')
    <x-adminlte-card theme="primary" theme-mode="outline" title="Details de la taxe" icon="fas fa-percentage">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="font-weight-bold">Code Taxe</label>
                    <p class="text-muted">{{ $tax->code_taxe }}</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="font-weight-bold">Libelle</label>
                    <p class="text-muted">{{ $tax->libelle }}</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="font-weight-bold">Taux (%)</label>
                    <p class="text-muted">{{ $tax->taux }}</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="font-weight-bold">Date de creation</label>
                    <p class="text-muted">{{ $tax->created_at?->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>
        <hr>
        <div class="form-group">
            @can('taxes.update')
                <a href="{{ route('taxes.edit', $tax) }}" class="btn btn-primary">
                    <i class="fas fa-pen mr-1"></i> Modifier
                </a>
            @endcan
            @can('taxes.delete')
                <form action="{{ route('taxes.destroy', $tax) }}" method="POST" onsubmit="return confirm('Etes-vous sur de vouloir supprimer cette taxe ?');" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash mr-1"></i> Supprimer
                    </button>
                </form>
            @endcan
            <a href="{{ route('taxes.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Retour
            </a>
        </div>
    </x-adminlte-card>
@stop
