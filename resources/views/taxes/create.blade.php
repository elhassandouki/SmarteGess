@extends('adminlte::page')

@section('title', 'Nouvelle Taxe')

@section('content_header')
    <h1 class="m-0 text-dark">Creer une nouvelle taxe</h1>
@stop

@section('content')
    @include('partials.flash')

    <div class="row justify-content-center">
        <div class="col-md-8">
            <x-adminlte-card theme="primary" theme-mode="outline" title="Formulaire de creation" icon="fas fa-plus">
                <form action="{{ route('taxes.store') }}" method="POST">
                    @csrf
                    @include('taxes._form')
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Creer
                        </button>
                        <a href="{{ route('taxes.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times mr-1"></i> Annuler
                        </a>
                    </div>
                </form>
            </x-adminlte-card>
        </div>
    </div>
@stop
