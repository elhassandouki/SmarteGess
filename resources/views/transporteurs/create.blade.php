@extends('adminlte::page')

@section('title', 'Nouveau transporteur')

@section('content_header')
    <h1 class="m-0 text-dark">Nouveau transporteur</h1>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="info" theme-mode="outline" title="Creer un transporteur" icon="fas fa-truck-loading">
        <form action="{{ route('transporteurs.store') }}" method="POST">
            @include('transporteurs._form')
        </form>
    </x-adminlte-card>
@stop
