@extends('adminlte::page')

@section('title', 'Modifier role')

@section('content_header')
    <h1 class="m-0 text-dark">Modifier role: {{ $role->name }}</h1>
@stop

@section('content')
    @include('partials.flash')
    <x-adminlte-card title="Edition role et permissions" icon="fas fa-user-cog" theme="primary" theme-mode="outline">
        <form action="{{ route('access.roles.update', $role) }}" method="POST">
            @include('access.roles._form')
        </form>
    </x-adminlte-card>
@stop

