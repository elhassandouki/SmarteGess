@extends('adminlte::page')

@section('title', 'Nouveau reglement')

@section('content_header')
    <h1 class="m-0 text-dark">Nouveau reglement</h1>
@stop

@section('content')
    @include('partials.flash')

    <x-adminlte-card theme="success" theme-mode="outline" title="Ajouter un reglement" icon="fas fa-money-check-alt">
        <form action="{{ route('reglements.store') }}" method="POST">
            @csrf

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="tier_id">Tiers</label>
                        <select name="tier_id" id="tier_id" class="form-control @error('tier_id') is-invalid @enderror" required>
                            <option value="">Selectionner</option>
                            @foreach ($tiers as $tier)
                                <option value="{{ $tier->id }}" @selected(old('tier_id') == $tier->id)>{{ $tier->code_tiers ?: $tier->ct_num }} - {{ $tier->ct_intitule }}</option>
                            @endforeach
                        </select>
                        @error('tier_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="doc_id">Document</label>
                        <select name="doc_id" id="doc_id" class="form-control @error('doc_id') is-invalid @enderror">
                            <option value="">Aucun document</option>
                            @foreach ($documents as $document)
                                <option value="{{ $document->id }}" @selected(old('doc_id') == $document->id)>{{ $document->do_piece }} - {{ optional($document->do_date)->format('Y-m-d') }}</option>
                            @endforeach
                        </select>
                        @error('doc_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="rg_date">Date</label>
                        <input type="date" name="rg_date" id="rg_date" class="form-control @error('rg_date') is-invalid @enderror" value="{{ old('rg_date', now()->format('Y-m-d')) }}" required>
                        @error('rg_date')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="rg_montant">Montant</label>
                        <input type="number" step="0.01" min="0.01" name="rg_montant" id="rg_montant" class="form-control @error('rg_montant') is-invalid @enderror" value="{{ old('rg_montant') }}" required>
                        @error('rg_montant')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="rg_mode_reglement">Mode</label>
                        <select name="rg_mode_reglement" id="rg_mode_reglement" class="form-control @error('rg_mode_reglement') is-invalid @enderror" required>
                            @foreach ($modes as $value => $label)
                                <option value="{{ $value }}" @selected((string) old('rg_mode_reglement', '1') === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('rg_mode_reglement')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="rg_reference">Reference</label>
                        <input type="text" name="rg_reference" id="rg_reference" class="form-control @error('rg_reference') is-invalid @enderror" value="{{ old('rg_reference') }}">
                        @error('rg_reference')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="rg_libelle">Libelle</label>
                        <input type="text" name="rg_libelle" id="rg_libelle" class="form-control @error('rg_libelle') is-invalid @enderror" value="{{ old('rg_libelle') }}">
                        @error('rg_libelle')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="rg_date_echeance">Date echeance</label>
                        <input type="date" name="rg_date_echeance" id="rg_date_echeance" class="form-control @error('rg_date_echeance') is-invalid @enderror" value="{{ old('rg_date_echeance') }}">
                        @error('rg_date_echeance')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="rg_banque">Banque</label>
                        <input type="text" name="rg_banque" id="rg_banque" class="form-control @error('rg_banque') is-invalid @enderror" value="{{ old('rg_banque') }}">
                        @error('rg_banque')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="custom-control custom-switch mb-3">
                <input type="checkbox" name="rg_valide" id="rg_valide" class="custom-control-input" value="1" @checked(old('rg_valide', true))>
                <label class="custom-control-label" for="rg_valide">Reglement valide</label>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('reglements.index') }}" class="btn btn-default">Retour</a>
                <button type="submit" class="btn btn-success">Enregistrer</button>
            </div>
        </form>
    </x-adminlte-card>
@stop
