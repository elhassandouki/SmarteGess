@extends('adminlte::page')

@section('title', 'Nouvelle famille')

@section('content_header')
    <h1 class="m-0 text-dark">Nouvelle famille</h1>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="primary" theme-mode="outline" title="Creer une famille" icon="fas fa-plus-circle">
        <form action="{{ route('families.store') }}" method="POST">
            @include('families._form')
        </form>
    </x-adminlte-card>
@stop
