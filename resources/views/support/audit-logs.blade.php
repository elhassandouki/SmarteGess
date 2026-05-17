@extends('adminlte::page')
@section('title', 'Audit Logs')
@section('content_header')<h1>Audit Logs</h1>@stop
@section('content')
<div class="card"><div class="card-body table-responsive p-0">
<table class="table table-sm table-striped mb-0">
<thead><tr><th>Date</th><th>Event</th><th>Entity</th><th>Actor</th><th>Severity</th><th>Payload</th></tr></thead>
<tbody>
@foreach($logs as $log)
<tr>
<td>{{ $log->created_at }}</td>
<td>{{ $log->event_type }}</td>
<td>{{ $log->entity_type }}#{{ $log->entity_id }}</td>
<td>{{ $log->actor_id ?? '-' }}</td>
<td>{{ $log->severity }}</td>
<td><pre class="mb-0" style="white-space:pre-wrap">{{ json_encode($log->payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre></td>
</tr>
@endforeach
</tbody>
</table></div>
<div class="card-footer">{{ $logs->links() }}</div></div>
@stop
