@extends('adminlte::page')

@section('title', 'Permissions')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark">Permissions</h1>
        @can('access.permissions.create')
            <a href="{{ route('access.permissions.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> Nouvelle permission
            </a>
        @endcan
    </div>
@stop

@section('content')
    @include('partials.flash')
    <x-adminlte-card title="Liste des permissions" icon="fas fa-key" theme="primary" theme-mode="outline">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Permission</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($permissions as $permission)
                    <tr>
                        <td>{{ $permission->name }}</td>
                        <td class="text-nowrap">
                            @can('access.permissions.update')
                                <a href="{{ route('access.permissions.edit', $permission) }}" class="btn btn-xs btn-outline-primary">
                                    <i class="fas fa-pen"></i>
                                </a>
                            @endcan
                            @can('access.permissions.delete')
                                <form action="{{ route('access.permissions.destroy', $permission) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette permission ?');">
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

