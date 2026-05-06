@csrf

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="code_depot">Code depot</label>
            <input type="text" name="code_depot" id="code_depot" class="form-control @error('code_depot') is-invalid @enderror"
                value="{{ old('code_depot', $depot->code_depot ?? '') }}" required>
            @error('code_depot')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-md-8">
        <div class="form-group">
            <label for="intitule">Intitule</label>
            <input type="text" name="intitule" id="intitule" class="form-control @error('intitule') is-invalid @enderror"
                value="{{ old('intitule', $depot->intitule ?? '') }}" required>
            @error('intitule')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="d-flex justify-content-between">
    <a href="{{ route('depots.index') }}" class="btn btn-default">Retour</a>
    <button type="submit" class="btn btn-primary">Enregistrer</button>
</div>
