@csrf

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="ct_num">Numero tiers</label>
            <input type="text" name="ct_num" id="ct_num" class="form-control @error('ct_num') is-invalid @enderror"
                value="{{ old('ct_num', $tier->ct_num ?? '') }}" required>
            @error('ct_num')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="code_tiers">Code tiers</label>
            <input type="text" name="code_tiers" id="code_tiers" class="form-control @error('code_tiers') is-invalid @enderror"
                value="{{ old('code_tiers', $tier->code_tiers ?? '') }}">
            <small class="form-text text-muted">Laisse vide pour reprendre automatiquement le numero tiers.</small>
            @error('code_tiers')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="ct_type">Type</label>
            <select name="ct_type" id="ct_type" class="form-control @error('ct_type') is-invalid @enderror" required>
                @foreach (['client' => 'Client', 'fournisseur' => 'Fournisseur', 'prospect' => 'Prospect'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('ct_type', $tier->ct_type ?? 'client') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('ct_type')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="form-group">
    <label for="ct_intitule">Intitule</label>
    <input type="text" name="ct_intitule" id="ct_intitule" class="form-control @error('ct_intitule') is-invalid @enderror"
        value="{{ old('ct_intitule', $tier->ct_intitule ?? '') }}" required>
    @error('ct_intitule')
        <span class="invalid-feedback">{{ $message }}</span>
    @enderror
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="ct_ice">ICE</label>
            <input type="text" name="ct_ice" id="ct_ice" class="form-control @error('ct_ice') is-invalid @enderror"
                value="{{ old('ct_ice', $tier->ct_ice ?? '') }}">
            @error('ct_ice')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="ct_if">IF</label>
            <input type="text" name="ct_if" id="ct_if" class="form-control @error('ct_if') is-invalid @enderror"
                value="{{ old('ct_if', $tier->ct_if ?? '') }}">
            @error('ct_if')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="ct_encours_max">Encours max</label>
            <input type="number" step="0.01" min="0" name="ct_encours_max" id="ct_encours_max"
                class="form-control @error('ct_encours_max') is-invalid @enderror"
                value="{{ old('ct_encours_max', $tier->ct_encours_max ?? 0) }}">
            @error('ct_encours_max')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="ct_delai_paiement">Delai paiement</label>
            <input type="number" min="0" name="ct_delai_paiement" id="ct_delai_paiement"
                class="form-control @error('ct_delai_paiement') is-invalid @enderror"
                value="{{ old('ct_delai_paiement', $tier->ct_delai_paiement ?? 0) }}">
            @error('ct_delai_paiement')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="ct_telephone">Telephone</label>
            <input type="text" name="ct_telephone" id="ct_telephone" class="form-control @error('ct_telephone') is-invalid @enderror"
                value="{{ old('ct_telephone', $tier->ct_telephone ?? '') }}">
            @error('ct_telephone')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-8">
        <div class="form-group">
            <label for="ct_adresse">Adresse</label>
            <input type="text" name="ct_adresse" id="ct_adresse" class="form-control @error('ct_adresse') is-invalid @enderror"
                value="{{ old('ct_adresse', $tier->ct_adresse ?? '') }}">
            @error('ct_adresse')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="d-flex justify-content-between">
    <a href="{{ route('tiers.index') }}" class="btn btn-default">Retour</a>
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save mr-1"></i> Enregistrer
    </button>
</div>
