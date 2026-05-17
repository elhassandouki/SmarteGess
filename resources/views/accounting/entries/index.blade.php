@extends('adminlte::page')

@section('title', 'Journal d\'Entrees Comptables')

@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="m-0 text-dark">Journal Comptable</h1>
            <small class="text-muted">Tous les enregistrements comptables de votre systeme.</small>
        </div>
    </div>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="primary" theme-mode="outline" title="Filtres" icon="fas fa-filter">
        <form method="GET" action="{{ route('accounting.entries.index') }}" class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="search">Recherche</label>
                    <input type="text" name="search" id="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Journal, reference ou libelle">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="status">Statut</label>
                    <select name="status" id="status" class="form-control">
                        <option value="">Tous</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" @if($filters['status'] === $key) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="from_date">De</label>
                    <input type="date" name="from_date" id="from_date" class="form-control" value="{{ $filters['from_date'] }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="to_date">Jusqu'au</label>
                    <input type="date" name="to_date" id="to_date" class="form-control" value="{{ $filters['to_date'] }}">
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                <button type="button" class="btn btn-outline-secondary js-reset-filters">Reset</button>
            </div>
        </form>
    </x-adminlte-card>

    <x-adminlte-card theme="primary" theme-mode="outline" title="Liste des entrees" icon="fas fa-list">
        <div class="table-responsive">
            <table id="entriesTable" class="table table-striped table-hover table-bordered mb-0">
                <thead class="thead-dark"><tr><th>Date</th><th>Journal</th><th>Reference</th><th>Libelle</th><th>Debit</th><th>Credit</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </x-adminlte-card>
@stop

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if ($.fn.DataTable.isDataTable('#entriesTable')) $('#entriesTable').DataTable().destroy();
    const t = $('#entriesTable').DataTable({
        processing:true, serverSide:true,
        ajax:{ 
            url:"{{ route('accounting.entries.index') }}", 
            data:d=>{ 
                d.search=$('#search').val();
                d.status=$('#status').val();
                d.from_date=$('#from_date').val();
                d.to_date=$('#to_date').val();
            }
        },
        columns:[{data:'entry_date'},{data:'journal_code'},{data:'reference_number'},{data:'label'},{data:'debit'},{data:'credit'},{data:'status'},{data:'actions',orderable:false,searchable:false}]
    });

    document.querySelector('.js-reset-filters')?.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('search').value = '';
        document.getElementById('status').value = '';
        document.getElementById('from_date').value = '';
        document.getElementById('to_date').value = '';
        document.querySelector('form').submit();
    });
});
</script>
@endpush
