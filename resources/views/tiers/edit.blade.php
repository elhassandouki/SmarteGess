@extends('adminlte::page')

@section('title', 'Modifier tiers')

@section('content_header')
    <h1 class="m-0 text-dark">Modifier le tiers</h1>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="primary" theme-mode="outline" title="Edition tiers" icon="fas fa-user-edit">
        <form action="{{ route('tiers.update', $tier) }}" method="POST">
            @method('PUT')
            @include('tiers._form')
        </form>
    </x-adminlte-card>
@stop
