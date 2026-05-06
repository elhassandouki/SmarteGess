@extends('adminlte::page')

@section('title', 'Modifier article')

@section('content_header')
    <h1 class="m-0 text-dark">Modifier l'article</h1>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="success" theme-mode="outline" title="Edition" icon="fas fa-pen">
        <form action="{{ route('articles.update', $article) }}" method="POST">
            @method('PUT')
            @include('articles._form')
        </form>
    </x-adminlte-card>
@stop
