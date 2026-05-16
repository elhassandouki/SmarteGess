@extends('adminlte::page')

@section('title', 'Nouveau role')

@section('content_header')
    <h1 class="m-0 text-dark">Nouveau role</h1>
@stop

@section('content')
    @include('partials.flash')
    <x-adminlte-card title="Creation d un role" icon="fas fa-plus-circle" theme="primary" theme-mode="outline">
        <form action="{{ route('access.roles.store') }}" method="POST">
            @include('access.roles._form', ['selectedPermissions' => []])
        </form>
    </x-adminlte-card>
@stop

