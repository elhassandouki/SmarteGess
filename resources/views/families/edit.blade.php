@extends('adminlte::page')

@section('title', 'Modifier famille')

@section('content_header')
    <h1 class="m-0 text-dark">Modifier la famille</h1>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="primary" theme-mode="outline" title="Edition" icon="fas fa-pen">
        <form action="{{ route('families.update', $family) }}" method="POST">
            @method('PUT')
            @include('families._form')
        </form>
    </x-adminlte-card>
@stop
