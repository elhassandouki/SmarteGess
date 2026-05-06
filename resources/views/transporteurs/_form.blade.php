@csrf

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="tr_nom">Nom</label>
            <input type="text" name="tr_nom" id="tr_nom" class="form-control @error('tr_nom') is-invalid @enderror"
                value="{{ old('tr_nom', $transporteur->tr_nom ?? '') }}" required>
            @error('tr_nom')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="tr_matricule">Matricule</label>
            <input type="text" name="tr_matricule" id="tr_matricule" class="form-control @error('tr_matricule') is-invalid @enderror"
                value="{{ old('tr_matricule', $transporteur->tr_matricule ?? '') }}">
            @error('tr_matricule')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="tr_chauffeur">Chauffeur</label>
            <input type="text" name="tr_chauffeur" id="tr_chauffeur" class="form-control @error('tr_chauffeur') is-invalid @enderror"
                value="{{ old('tr_chauffeur', $transporteur->tr_chauffeur ?? '') }}">
            @error('tr_chauffeur')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="tr_telephone">Telephone</label>
            <input type="text" name="tr_telephone" id="tr_telephone" class="form-control @error('tr_telephone') is-invalid @enderror"
                value="{{ old('tr_telephone', $transporteur->tr_telephone ?? '') }}">
            @error('tr_telephone')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="d-flex justify-content-between">
    <a href="{{ route('transporteurs.index') }}" class="btn btn-default">Retour</a>
    <button type="submit" class="btn btn-info">
        <i class="fas fa-save mr-1"></i> Enregistrer
    </button>
</div>
