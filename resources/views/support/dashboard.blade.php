@extends('adminlte::page')

@section('title', 'Support Ops')

@section('content_header')
<h1>Support & Operations</h1>
@stop

@section('content')
<div class="row">
  <div class="col-md-2"><div class="small-box bg-info"><div class="inner"><h3>{{ $stats['tenants_total'] }}</h3><p>Tenants</p></div></div></div>
  <div class="col-md-2"><div class="small-box bg-success"><div class="inner"><h3>{{ $stats['tenants_active'] }}</h3><p>Actifs</p></div></div></div>
  <div class="col-md-2"><div class="small-box bg-warning"><div class="inner"><h3>{{ $stats['outbox_pending'] }}</h3><p>Outbox pending</p></div></div></div>
  <div class="col-md-2"><div class="small-box bg-danger"><div class="inner"><h3>{{ $stats['outbox_failed'] }}</h3><p>Outbox failed</p></div></div></div>
  <div class="col-md-2"><div class="small-box bg-secondary"><div class="inner"><h3>{{ $stats['failed_jobs'] }}</h3><p>Failed jobs</p></div></div></div>
  <div class="col-md-2"><div class="small-box bg-primary"><div class="inner"><h3>{{ $stats['audit_last_24h'] }}</h3><p>Audit 24h</p></div></div></div>
</div>

<div class="card">
  <div class="card-header"><h3 class="card-title">Tenants</h3></div>
  <div class="card-body table-responsive p-0">
    <table class="table table-striped">
      <thead><tr><th>ID</th><th>Nom</th><th>Slug</th><th>Actif</th><th>Action</th></tr></thead>
      <tbody>
      @foreach($tenants as $tenant)
        <tr>
          <td>{{ $tenant->id }}</td>
          <td>{{ $tenant->name }}</td>
          <td>{{ $tenant->slug }}</td>
          <td>{{ $tenant->is_active ? 'Oui' : 'Non' }}</td>
          <td>
            <form method="POST" action="{{ route('support.tenants.toggle', $tenant) }}">@csrf @method('PATCH')
              <button class="btn btn-sm btn-outline-primary">{{ $tenant->is_active ? 'Suspendre' : 'Activer' }}</button>
            </form>
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</div>
@stop
