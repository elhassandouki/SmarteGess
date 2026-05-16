@extends('adminlte::page')

@section('title', 'Roles')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark">Roles</h1>
        @can('access.roles.create')
            <a href="{{ route('access.roles.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> Nouveau role
            </a>
        @endcan
    </div>
@stop

@section('content')
    @include('partials.flash')
    <x-adminlte-card title="Liste des roles" icon="fas fa-user-shield" theme="primary" theme-mode="outline">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Role</th>
                    <th>Nb permissions</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($roles as $role)
                    <tr>
                        <td>{{ $role->name }}</td>
                        <td>{{ $role->permissions_count }}</td>
                        <td class="text-nowrap">
                            @can('access.roles.update')
                                <a href="{{ route('access.roles.edit', $role) }}" class="btn btn-xs btn-outline-primary">
                                    <i class="fas fa-pen"></i>
                                </a>
                            @endcan
                            @can('access.roles.delete')
                                <form action="{{ route('access.roles.destroy', $role) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce role ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-xs btn-outline-danger" type="submit"><i class="fas fa-trash"></i></button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </x-adminlte-card>
@stop

