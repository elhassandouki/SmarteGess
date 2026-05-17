<div class="form-group">
    <label for="code_taxe">Code Taxe <span class="text-danger">*</span></label>
    <input 
        type="text" 
        name="code_taxe" 
        id="code_taxe" 
        class="form-control @error('code_taxe') is-invalid @enderror" 
        value="{{ old('code_taxe', $tax->code_taxe ?? '') }}"
        placeholder="TVA0, TVA7, TVA20, etc."
        @if($tax ?? false) readonly @endif
        required
    >
    @error('code_taxe')
        <span class="invalid-feedback">{{ $message }}</span>
    @enderror
</div>

<div class="form-group">
    <label for="libelle">Libelle <span class="text-danger">*</span></label>
    <input 
        type="text" 
        name="libelle" 
        id="libelle" 
        class="form-control @error('libelle') is-invalid @enderror" 
        value="{{ old('libelle', $tax->libelle ?? '') }}"
        placeholder="Ex: TVA 20%"
        required
    >
    @error('libelle')
        <span class="invalid-feedback">{{ $message }}</span>
    @enderror
</div>

<div class="form-group">
    <label for="taux">Taux (%) <span class="text-danger">*</span></label>
    <input 
        type="number" 
        name="taux" 
        id="taux" 
        class="form-control @error('taux') is-invalid @enderror" 
        value="{{ old('taux', $tax->taux ?? '') }}"
        placeholder="20"
        min="0"
        max="100"
        step="0.01"
        required
    >
    @error('taux')
        <span class="invalid-feedback">{{ $message }}</span>
    @enderror
</div>
