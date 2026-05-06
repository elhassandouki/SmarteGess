@extends('adminlte::page')

@section('title', 'Modifier document')
@section('plugins.Select2', true)

@section('content_header')
    <h1 class="m-0 text-dark">Modifier le document</h1>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="warning" theme-mode="outline" title="Edition" icon="fas fa-pen">
        <form action="{{ route('documents.update', $document) }}" method="POST">
            @method('PUT')
            @include('documents._form')
        </form>
    </x-adminlte-card>
@stop
