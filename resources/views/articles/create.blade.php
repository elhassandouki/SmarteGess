@extends('adminlte::page')

@section('title', 'Nouvel article')

@section('content_header')
    <h1 class="m-0 text-dark">Nouvel article</h1>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="success" theme-mode="outline" title="Creer un article" icon="fas fa-box-open">
        <form action="{{ route('articles.store') }}" method="POST">
            @include('articles._form')
        </form>
    </x-adminlte-card>
@stop
