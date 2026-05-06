@extends('adminlte::page')

@section('title', 'Nouveau tiers')

@section('content_header')
    <h1 class="m-0 text-dark">Nouveau tiers</h1>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="primary" theme-mode="outline" title="Creer un tiers" icon="fas fa-user-plus">
        <form action="{{ route('tiers.store') }}" method="POST">
            @include('tiers._form')
        </form>
    </x-adminlte-card>
@stop
