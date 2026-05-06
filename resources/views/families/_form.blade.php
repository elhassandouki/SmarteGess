@csrf

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="fa_code">Code</label>
            <input type="text" name="fa_code" id="fa_code" class="form-control @error('fa_code') is-invalid @enderror"
                value="{{ old('fa_code', $family->fa_code ?? '') }}" required>
            @error('fa_code')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-8">
        <div class="form-group">
            <label for="fa_intitule">Intitule</label>
            <input type="text" name="fa_intitule" id="fa_intitule" class="form-control @error('fa_intitule') is-invalid @enderror"
                value="{{ old('fa_intitule', $family->fa_intitule ?? '') }}" required>
            @error('fa_intitule')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="d-flex justify-content-between">
    <a href="{{ route('families.index') }}" class="btn btn-default">Retour</a>
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save mr-1"></i> Enregistrer
    </button>
</div>
