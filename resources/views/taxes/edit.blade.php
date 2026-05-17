@extends('adminlte::page')

@section('title', 'Modifier - ' . $tax->libelle)

@section('content_header')
    <h1 class="m-0 text-dark">Modifier la taxe: {{ $tax->libelle }}</h1>
@stop

@section('content')
    @include('partials.flash')

    <div class="row justify-content-center">
        <div class="col-md-8">
            <x-adminlte-card theme="primary" theme-mode="outline" title="Formulaire de modification" icon="fas fa-pen">
                <form action="{{ route('taxes.update', $tax) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('taxes._form')
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Enregistrer
                        </button>
                        <a href="{{ route('taxes.show', $tax) }}" class="btn btn-secondary">
                            <i class="fas fa-times mr-1"></i> Annuler
                        </a>
                    </div>
                </form>
            </x-adminlte-card>
        </div>
    </div>
@stop
