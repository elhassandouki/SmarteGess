@csrf

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="code_article">Code article</label>
            <input type="text" name="code_article" id="code_article" class="form-control @error('code_article') is-invalid @enderror"
                value="{{ old('code_article', $article->code_article ?? '') }}">
            <small class="form-text text-muted">Laisse vide pour reprendre automatiquement la reference.</small>
            @error('code_article')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="ar_ref">Reference</label>
            <input type="text" name="ar_ref" id="ar_ref" class="form-control @error('ar_ref') is-invalid @enderror"
                value="{{ old('ar_ref', $article->ar_ref ?? '') }}" required>
            @error('ar_ref')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="ar_code_barre">Code-barres</label>
            <input type="text" name="ar_code_barre" id="ar_code_barre" class="form-control @error('ar_code_barre') is-invalid @enderror"
                value="{{ old('ar_code_barre', $article->ar_code_barre ?? '') }}">
            @error('ar_code_barre')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="form-group">
            <label for="ar_design">Designation</label>
            <input type="text" name="ar_design" id="ar_design" class="form-control @error('ar_design') is-invalid @enderror"
                value="{{ old('ar_design', $article->ar_design ?? '') }}" required>
            @error('ar_design')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="family_id">Famille</label>
            <select name="family_id" id="family_id" class="form-control @error('family_id') is-invalid @enderror">
                <option value="">Selectionner une famille</option>
                @foreach ($families as $family)
                    <option value="{{ $family->id }}" @selected(old('family_id', $article->family_id ?? '') == $family->id)>
                        {{ $family->fa_intitule }}
                    </option>
                @endforeach
            </select>
            @error('family_id')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="ar_unite">Unite</label>
            <input type="text" name="ar_unite" id="ar_unite" class="form-control @error('ar_unite') is-invalid @enderror"
                value="{{ old('ar_unite', $article->ar_unite ?? 'Pcs') }}" required>
            @error('ar_unite')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="ar_tva">TVA %</label>
            <input type="number" step="0.01" min="0" max="100" name="ar_tva" id="ar_tva"
                class="form-control @error('ar_tva') is-invalid @enderror"
                value="{{ old('ar_tva', $article->ar_tva ?? 20) }}" required>
            @error('ar_tva')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="ar_stock_min">Stock minimum</label>
            <input type="number" step="0.001" min="0" name="ar_stock_min" id="ar_stock_min"
                class="form-control @error('ar_stock_min') is-invalid @enderror"
                value="{{ old('ar_stock_min', $article->ar_stock_min ?? 0) }}" required>
            @error('ar_stock_min')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="ar_stock_actuel">Stock actuel</label>
            <input type="number" step="0.001" min="0" name="ar_stock_actuel" id="ar_stock_actuel"
                class="form-control @error('ar_stock_actuel') is-invalid @enderror"
                value="{{ old('ar_stock_actuel', $article->ar_stock_actuel ?? 0) }}" required>
            @error('ar_stock_actuel')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="ar_prix_achat">Prix achat</label>
            <input type="number" step="0.00001" min="0" name="ar_prix_achat" id="ar_prix_achat"
                class="form-control @error('ar_prix_achat') is-invalid @enderror"
                value="{{ old('ar_prix_achat', $article->ar_prix_achat ?? 0) }}" required>
            @error('ar_prix_achat')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="ar_prix_vente">Prix vente</label>
            <input type="number" step="0.00001" min="0" name="ar_prix_vente" id="ar_prix_vente"
                class="form-control @error('ar_prix_vente') is-invalid @enderror"
                value="{{ old('ar_prix_vente', $article->ar_prix_vente ?? 0) }}" required>
            @error('ar_prix_vente')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="ar_prix_revient">Prix revient</label>
            <input type="number" step="0.00001" min="0" name="ar_prix_revient" id="ar_prix_revient"
                class="form-control @error('ar_prix_revient') is-invalid @enderror"
                value="{{ old('ar_prix_revient', $article->ar_prix_revient ?? $article->ar_prix_achat ?? 0) }}">
            <small class="form-text text-muted">Laisse vide pour reprendre automatiquement le prix achat.</small>
            @error('ar_prix_revient')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="form-group">
    <div class="custom-control custom-switch">
        <input type="checkbox" name="ar_suivi_stock" id="ar_suivi_stock" class="custom-control-input" value="1"
            @checked(old('ar_suivi_stock', $article->ar_suivi_stock ?? true))>
        <label class="custom-control-label" for="ar_suivi_stock">Activer le suivi du stock</label>
    </div>
    @error('ar_suivi_stock')
        <span class="text-danger small d-block mt-1">{{ $message }}</span>
    @enderror
</div>

<div class="d-flex justify-content-between">
    <a href="{{ route('articles.index') }}" class="btn btn-default">Retour</a>
    <button type="submit" class="btn btn-success">
        <i class="fas fa-save mr-1"></i> Enregistrer
    </button>
</div>

@push('js')
<script>
    (() => {
        const codeField = document.getElementById('code_article');
        const refField = document.getElementById('ar_ref');

        if (!codeField || !refField) {
            return;
        }

        let codeTouched = codeField.value.trim() !== '';

        codeField.addEventListener('input', () => {
            codeTouched = codeField.value.trim() !== '';
        });

        refField.addEventListener('input', () => {
            if (!codeTouched) {
                codeField.value = refField.value;
            }
        });
    })();
</script>
@endpush
