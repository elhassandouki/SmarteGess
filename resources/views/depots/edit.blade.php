@extends('adminlte::page')

@section('title', 'Modifier depot')

@section('content_header')
    <h1 class="m-0 text-dark">Modifier le depot</h1>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="primary" theme-mode="outline" title="Edition depot" icon="fas fa-pen">
        <form action="{{ route('depots.update', $depot) }}" method="POST">
            @method('PUT')
            @include('depots._form')
        </form>
    </x-adminlte-card>
@stop
