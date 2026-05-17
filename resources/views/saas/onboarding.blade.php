@extends('adminlte::page')

@section('title', 'Onboarding')

@section('content_header')
<h1>Configuration initiale</h1>
@stop

@section('content')
@include('partials.flash')
<div class="card card-primary">
  <div class="card-header"><h3 class="card-title">Assistant de demarrage</h3></div>
  <form method="POST" action="{{ route('saas.onboarding.store') }}">
    @csrf
    <div class="card-body">
      <div class="form-group">
        <label>Nom de societe</label>
        <input type="text" name="company_name" class="form-control" required>
      </div>
      <div class="form-row">
        <div class="form-group col-md-6"><label>ICE</label><input type="text" name="company_ice" class="form-control"></div>
        <div class="form-group col-md-6"><label>IF</label><input type="text" name="company_if" class="form-control"></div>
      </div>
      <div class="form-row">
        <div class="form-group col-md-6"><label>Telephone</label><input type="text" name="company_phone" class="form-control"></div>
        <div class="form-group col-md-6"><label>Email</label><input type="email" name="company_email" class="form-control"></div>
      </div>
      <div class="form-group"><label>Adresse</label><input type="text" name="company_address" class="form-control"></div>
      <div class="form-row">
        <div class="form-group col-md-6"><label>Prefixe facture</label><input type="text" name="invoice_prefix" value="FAC" class="form-control"></div>
        <div class="form-group col-md-6">
          <label>Plan</label>
          <select name="plan_code" class="form-control">
            @foreach($plans as $plan)
              <option value="{{ $plan->code }}">{{ $plan->name }} - {{ number_format((float)$plan->price_mad,0,',',' ') }} MAD/mois</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="form-group form-check">
        <input type="checkbox" class="form-check-input" id="demo_mode" name="demo_mode" value="1">
        <label class="form-check-label" for="demo_mode">Activer le mode demo (donnees d'exemple)</label>
      </div>
    </div>
    <div class="card-footer">
      <button class="btn btn-primary" type="submit">Finaliser la configuration</button>
    </div>
  </form>
</div>
@stop
