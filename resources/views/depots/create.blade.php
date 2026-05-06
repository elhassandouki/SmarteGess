@extends('adminlte::page')

@section('title', 'Nouveau depot')

@section('content_header')
    <h1 class="m-0 text-dark">Nouveau depot</h1>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="primary" theme-mode="outline" title="Creer un depot" icon="fas fa-warehouse">
        <form action="{{ route('depots.store') }}" method="POST">
            @include('depots._form')
        </form>
    </x-adminlte-card>
@stop
