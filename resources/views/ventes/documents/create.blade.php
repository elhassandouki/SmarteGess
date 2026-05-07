@extends('adminlte::page')

@section('title', 'Nouveau document')
@section('plugins.Select2', true)

@section('content_header')
    <h1 class="m-0 text-dark">Nouveau document</h1>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="warning" theme-mode="outline" title="Creer un document" icon="fas fa-file-medical">
        <form action="{{ route('documents.store') }}" method="POST">
            @include('documents._form')
        </form>
    </x-adminlte-card>
@stop
