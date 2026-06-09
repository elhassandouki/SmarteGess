@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="m-0 text-dark">AI Reporting & Intelligence</h1>
            <p class="text-muted mb-0">Rapports automatiques, alertes stock predictives, exports PDF/Excel.</p>
        </div>
    </div>
    <div class="alert alert-info">
        Utilisez les endpoints API suivants: <code>/ai/reports/run</code>, <code>/ai/stock-alerts</code>, <code>/ai/reports/export/{format}</code>.
    </div>
</div>
@endsection

