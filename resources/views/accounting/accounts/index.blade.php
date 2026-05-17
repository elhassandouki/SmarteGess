@extends('adminlte::page')

@section('title', 'Chart of Accounts')

@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="m-0 text-dark">Plan Comptable</h1>
            <small class="text-muted">Tous les comptes de votre plan comptable.</small>
        </div>
    </div>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="primary" theme-mode="outline" title="Filtres" icon="fas fa-filter">
        <form method="GET" action="{{ route('accounting.accounts.index') }}" class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="search">Recherche</label>
                    <input type="text" name="search" id="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Code ou intitule">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="type">Type de compte</label>
                    <select name="type" id="type" class="form-control">
                        <option value="">Tous</option>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}" @if($filters['type'] === $key) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-check mt-4">
                    <input type="checkbox" name="active_only" id="active_only" class="form-check-input" @if($filters['active_only']) checked @endif>
                    <label class="form-check-label" for="active_only">Actifs uniquement</label>
                </div>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                <button type="button" class="btn btn-outline-secondary js-reset-filters">Reset</button>
            </div>
        </form>
    </x-adminlte-card>

    <x-adminlte-card theme="primary" theme-mode="outline" title="Liste des comptes" icon="fas fa-list">
        <div class="table-responsive">
            <table id="accountsTable" class="table table-striped table-hover table-bordered mb-0">
                <thead class="thead-dark"><tr><th>Code</th><th>Libelle</th><th>Type</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </x-adminlte-card>
@stop

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if ($.fn.DataTable.isDataTable('#accountsTable')) $('#accountsTable').DataTable().destroy();
    const t = $('#accountsTable').DataTable({
        processing:true, serverSide:true,
        ajax:{ 
            url:"{{ route('accounting.accounts.index') }}", 
            data:d=>{ 
                d.search=$('#search').val();
                d.type=$('#type').val();
                d.active_only=$('#active_only').is(':checked') ? 1 : 0;
            }
        },
        columns:[{data:'account_code'},{data:'account_label'},{data:'account_type'},{data:'is_active'},{data:'actions',orderable:false,searchable:false}]
    });

    document.querySelector('.js-reset-filters')?.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('search').value = '';
        document.getElementById('type').value = '';
        document.getElementById('active_only').checked = true;
        document.querySelector('form').submit();
    });
});
</script>
@endpush
