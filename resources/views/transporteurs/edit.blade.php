@extends('adminlte::page')

@section('title', 'Modifier transporteur')

@section('content_header')
    <h1 class="m-0 text-dark">Modifier le transporteur</h1>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="info" theme-mode="outline" title="Edition" icon="fas fa-pen">
        <form action="{{ route('transporteurs.update', $transporteur) }}" method="POST">
            @method('PUT')
            @include('transporteurs._form')
        </form>
    </x-adminlte-card>
@stop
