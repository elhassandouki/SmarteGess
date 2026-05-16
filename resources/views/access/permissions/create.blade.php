@extends('adminlte::page')

@section('title', 'Nouvelle permission')

@section('content_header')
    <h1 class="m-0 text-dark">Nouvelle permission</h1>
@stop

@section('content')
    @include('partials.flash')
    <x-adminlte-card title="Creation permission" icon="fas fa-plus-circle" theme="primary" theme-mode="outline">
        <form method="POST" action="{{ route('access.permissions.store') }}">
            @csrf
            <div class="form-group">
                <label for="name">Nom permission</label>
                <input id="name" type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="{{ route('access.permissions.index') }}" class="btn btn-default">Annuler</a>
        </form>
    </x-adminlte-card>
@stop

