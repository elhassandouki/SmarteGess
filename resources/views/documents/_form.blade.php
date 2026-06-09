@csrf
@if (!empty($module))
    <input type="hidden" name="module" value="{{ $module }}">
@endif

@php
    $currentLines = old('lines');

    if ($currentLines === null) {
        $currentLines = isset($document) && $document->lines->isNotEmpty()
            ? $document->lines->map(fn ($line) => [
                'article_id' => $line->article_id,
                'dl_qte' => $line->dl_qte,
                'dl_prix_unitaire_ht' => $line->dl_prix_unitaire_ht,
                'dl_remise_percent' => $line->dl_remise_percent,
            ])->toArray()
            : [[]];
    }

    if (empty($currentLines)) {
        $currentLines = [[]];
    }

    $articleMap = $articles->mapWithKeys(fn ($article) => [
        $article->id => [
            'id' => $article->id,
            'code' => $article->code_article ?: $article->ar_ref,
            'label' => ($article->code_article ?: $article->ar_ref).' - '.$article->ar_design,
            'price' => (float) $article->ar_prix_vente,
            'buy_price' => (float) $article->ar_prix_achat,
            'tva' => (float) $article->ar_tva,
            'stock' => (float) $article->ar_stock_actuel,
        ],
    ]);

    $articleOptions = $articles->map(fn ($article) => [
        'id' => $article->id,
        'label' => ($article->code_article ?: $article->ar_ref).' - '.$article->ar_design,
    ])->values();
@endphp

<div class="row">
    <div class="col-lg-8">
        <x-adminlte-card theme="warning" theme-mode="outline" title="En-tete document" icon="fas fa-file-alt">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="do_piece">Piece *</label>
                        <input type="text" name="do_piece" id="do_piece" class="form-control @error('do_piece') is-invalid @enderror" value="{{ old('do_piece', $document->do_piece ?? '') }}" required>
                        @error('do_piece') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="do_date">Date *</label>
                        <input type="date" name="do_date" id="do_date" class="form-control @error('do_date') is-invalid @enderror" value="{{ old('do_date', isset($document) ? optional($document->do_date)->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
                        @error('do_date') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="type_document_code">Type *</label>
                        <select name="type_document_code" id="type_document_code" class="form-control @error('type_document_code') is-invalid @enderror" required>
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}" @selected(old('type_document_code', $document->type_document_code ?? 'FA') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type_document_code') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="do_expedition_statut">Statut *</label>
                        <select name="do_expedition_statut" id="do_expedition_statut" class="form-control @error('do_expedition_statut') is-invalid @enderror" required>
                            @foreach ($statuts as $value => $label)
                                <option value="{{ $value }}" @selected(old('do_expedition_statut', $document->do_expedition_statut ?? 'en_attente') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('do_expedition_statut') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="tier_id">Client / Fournisseur *</label>
                        <select name="tier_id" id="tier_id" class="form-control @error('tier_id') is-invalid @enderror" required>
                            <option value="">Selectionner</option>
                            @foreach ($tiers as $tier)
                                <option value="{{ $tier->id }}" @selected((int) old('tier_id', $document->tier_id ?? 0) === $tier->id)>
                                    {{ $tier->code_tiers ?: $tier->ct_num }} - {{ $tier->ct_intitule }}
                                </option>
                            @endforeach
                        </select>
                        @error('tier_id') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="depot_id">Depot</label>
                        <select name="depot_id" id="depot_id" class="form-control @error('depot_id') is-invalid @enderror">
                            <option value="">Selectionner</option>
                            @foreach ($depots as $depot)
                                <option value="{{ $depot->id }}" @selected((int) old('depot_id', $document->depot_id ?? 0) === $depot->id)>{{ $depot->intitule }}</option>
                            @endforeach
                        </select>
                        @error('depot_id') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="transporteur_id">Transporteur</label>
                        <select name="transporteur_id" id="transporteur_id" class="form-control @error('transporteur_id') is-invalid @enderror">
                            <option value="">Selectionner</option>
                            @foreach ($transporteurs as $transporteur)
                                <option value="{{ $transporteur->id }}" @selected((int) old('transporteur_id', $document->transporteur_id ?? 0) === $transporteur->id)>{{ $transporteur->tr_nom }}</option>
                            @endforeach
                        </select>
                        @error('transporteur_id') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="do_date_livraison">Date livraison</label>
                        <input type="date" name="do_date_livraison" id="do_date_livraison" class="form-control @error('do_date_livraison') is-invalid @enderror" value="{{ old('do_date_livraison', isset($document) ? optional($document->do_date_livraison)->format('Y-m-d') : '') }}">
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="do_lieu_livraison">Lieu de livraison</label>
                        <input type="text" name="do_lieu_livraison" id="do_lieu_livraison" class="form-control @error('do_lieu_livraison') is-invalid @enderror" value="{{ old('do_lieu_livraison', $document->do_lieu_livraison ?? '') }}" placeholder="Adresse ou lieu de livraison">
                    </div>
                </div>
            </div>
        </x-adminlte-card>

        <x-adminlte-card theme="primary" theme-mode="outline" title="Lignes document" icon="fas fa-list">
            <div class="mb-3 p-2 border rounded bg-light">
                <div class="form-row align-items-end">
                    <div class="col-md-7 position-relative">
                        <label for="posProductInput" class="mb-1">Scan / Recherche produit</label>
                        <input type="text" id="posProductInput" class="form-control form-control-sm" placeholder="Scanner code-barres ou taper nom/code..." autocomplete="off">
                        <div id="posSearchResults" class="list-group position-absolute w-100 shadow-sm" style="z-index:1040; max-height:240px; overflow:auto; display:none;"></div>
                    </div>
                    <div class="col-md-2">
                        <label for="posQtyInput" class="mb-1">Qte</label>
                        <input type="number" id="posQtyInput" class="form-control form-control-sm" min="0.001" step="0.001" value="1">
                    </div>
                    <div class="col-md-3">
                        <button type="button" id="posAddFallbackBtn" class="btn btn-sm btn-outline-secondary btn-block">Ajouter via ligne manuelle</button>
                    </div>
                </div>
                <div class="mt-2 d-flex flex-wrap align-items-center">
                    <small class="text-muted mr-3">Mode POS: scanner puis Entrer. Le curseur reste sur ce champ pour un enchainement rapide.</small>
                    <button type="button" id="posCameraBtn" class="btn btn-xs btn-outline-info">Scanner camera (mobile)</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0" id="linesTable">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 30%;">Article</th>
                            <th style="width: 9%;">Qte</th>
                            <th style="width: 12%;">Prix HT</th>
                            <th style="width: 10%;">Remise %</th>
                            <th style="width: 8%;">TVA %</th>
                            <th style="width: 12%;">Montant HT</th>
                            <th style="width: 12%;">Montant TTC</th>
                            <th style="width: 7%;"></th>
                        </tr>
                    </thead>
                    <tbody id="linesBody">
                        @foreach ($currentLines as $idx => $line)
                            <tr class="line-row">
                                <td>
                                    <select name="lines[{{ $idx }}][article_id]" class="form-control form-control-sm line-article">
                                        <option value="">Selectionner</option>
                                        @foreach ($articles as $article)
                                            <option value="{{ $article->id }}" @selected((int) ($line['article_id'] ?? 0) === $article->id)>
                                                {{ $article->code_article ?: $article->ar_ref }} - {{ $article->ar_design }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.001" min="0" name="lines[{{ $idx }}][dl_qte]" class="form-control form-control-sm line-qty" value="{{ $line['dl_qte'] ?? 1 }}"></td>
                                <td><input type="number" step="0.00001" min="0" name="lines[{{ $idx }}][dl_prix_unitaire_ht]" class="form-control form-control-sm line-price" value="{{ $line['dl_prix_unitaire_ht'] ?? 0 }}"></td>
                                <td><input type="number" step="0.01" min="0" max="100" name="lines[{{ $idx }}][dl_remise_percent]" class="form-control form-control-sm line-discount" value="{{ $line['dl_remise_percent'] ?? 0 }}"></td>
                                <td><input type="text" class="form-control form-control-sm line-tva" value="0.00" readonly></td>
                                <td><input type="text" class="form-control form-control-sm line-ht" value="0.00" readonly></td>
                                <td><input type="text" class="form-control form-control-sm line-ttc" value="0.00" readonly></td>
                                <td class="text-center"><button type="button" class="btn btn-xs btn-outline-danger btn-remove-line"><i class="fas fa-times"></i></button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-between align-items-center">
                <button type="button" id="addLineBtn" class="btn btn-sm btn-outline-primary"><i class="fas fa-plus mr-1"></i> Ajouter une ligne</button>
                <small class="text-muted">Ajout instantane et recalcul automatique HT/TVA/TTC.</small>
            </div>
        </x-adminlte-card>
    </div>

    <div class="col-lg-4">
        <x-adminlte-card theme="success" theme-mode="outline" title="Resume financier" icon="fas fa-calculator">
            <dl class="row mb-0">
                <dt class="col-7">Total HT</dt><dd class="col-5 text-right" id="sumHt">0.00</dd>
                <dt class="col-7">Total TVA</dt><dd class="col-5 text-right" id="sumTva">0.00</dd>
                <dt class="col-7">Total TTC</dt><dd class="col-5 text-right font-weight-bold h5" id="sumTtc">0.00</dd>
            </dl>
            <hr>
            <div class="text-muted small">Le statut de paiement se met a jour via les reglements associes.</div>
            <div class="mt-3">
                <a href="{{ route('documents.index') }}" class="btn btn-default">Annuler</a>
                <button type="submit" class="btn btn-warning float-right"><i class="fas fa-save mr-1"></i> Enregistrer</button>
            </div>
        </x-adminlte-card>
    </div>
</div>

@push('js')
<script src="{{ asset('js/document-pos-builder.js') }}"></script>
<script>
    window.initDocumentPosBuilder?.({
        articleMap: @json($articleMap),
        articleOptions: @json($articleOptions),
        endpoints: {
            search: @json(route('api.articles.search')),
            barcode: "{{ url('api/articles/barcode/__CODE__') }}",
        },
    });
</script>
@endpush
