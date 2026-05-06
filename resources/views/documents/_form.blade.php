@csrf

@php
    $currentLines = old('lines');

    $articleOptions = $articles->map(fn ($article) => [
        'id' => $article->id,
        'code' => $article->code_article ?: $article->ar_ref,
        'ref' => $article->ar_ref,
        'design' => $article->ar_design,
        'label' => ($article->code_article ?: $article->ar_ref).' - '.$article->ar_design,
        'barcode' => $article->ar_code_barre,
        'price' => (float) $article->ar_prix_vente,
        'buy_price' => (float) $article->ar_prix_achat,
        'tva' => (float) $article->ar_tva,
        'unit' => $article->ar_unite,
        'stock' => (float) $article->ar_stock_actuel,
        'stock_min' => (float) $article->ar_stock_min,
        'track_stock' => (bool) $article->ar_suivi_stock,
    ])->values();

    if ($currentLines === null) {
        $currentLines = isset($document) && $document->lines->isNotEmpty()
            ? $document->lines->map(fn ($line) => [
                'article_id' => $line->article_id,
                'dl_qte' => $line->dl_qte,
                'dl_prix_unitaire_ht' => $line->dl_prix_unitaire_ht,
                'dl_remise_percent' => $line->dl_remise_percent,
            ])->toArray()
            : [];
    }

    $articleCatalog = $articleOptions->keyBy('id');
    $initialCart = collect($currentLines)
        ->filter(fn ($line) => ! empty($line['article_id']))
        ->map(function ($line) use ($articleCatalog) {
            $article = $articleCatalog->get((int) $line['article_id']);

            if (! $article) {
                return null;
            }

            return [
                'article_id' => (int) $line['article_id'],
                'dl_qte' => (float) ($line['dl_qte'] ?? 1),
                'dl_prix_unitaire_ht' => (float) ($line['dl_prix_unitaire_ht'] ?? $article['price']),
                'dl_remise_percent' => (float) ($line['dl_remise_percent'] ?? 0),
            ];
        })
        ->filter()
        ->values();
@endphp

@push('css')
<style>
    .touch-sale {
        --sale-navy: #16324f;
        --sale-blue: #1d5b79;
        --sale-cyan: #2aa7b8;
        --sale-sand: #f3efe6;
        --sale-ink: #243447;
        --sale-muted: #6e7f91;
        --sale-border: #d9e2ea;
        --sale-warn: #f59e0b;
        --sale-danger: #dc2626;
        --sale-soft: #f7fafc;
        color: var(--sale-ink);
    }

    .touch-sale .sale-shell {
        background:
            radial-gradient(circle at top left, rgba(42, 167, 184, .18), transparent 28%),
            linear-gradient(180deg, #fbfdff 0%, #eef4f8 100%);
        border: 1px solid #d9e4eb;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 24px 60px rgba(22, 50, 79, .08);
    }

    .touch-sale .sale-topbar {
        background: linear-gradient(135deg, var(--sale-navy) 0%, var(--sale-blue) 60%, var(--sale-cyan) 100%);
        color: #fff;
        padding: 1.25rem 1.5rem;
    }

    .touch-sale .sale-kpis {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .75rem;
        margin-top: 1rem;
    }

    .touch-sale .sale-kpi {
        background: rgba(255, 255, 255, .12);
        border: 1px solid rgba(255, 255, 255, .18);
        border-radius: 16px;
        padding: .75rem .9rem;
        backdrop-filter: blur(8px);
    }

    .touch-sale .sale-kpi-label {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        opacity: .75;
    }

    .touch-sale .sale-kpi-value {
        font-size: 1.1rem;
        font-weight: 700;
        margin-top: .2rem;
    }

    .touch-sale .sale-main {
        padding: 1.25rem;
    }

    .touch-sale .sale-panel {
        background: rgba(255, 255, 255, .84);
        border: 1px solid var(--sale-border);
        border-radius: 20px;
        box-shadow: 0 10px 28px rgba(28, 59, 86, .05);
    }

    .touch-sale .sale-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.1rem;
        border-bottom: 1px solid var(--sale-border);
    }

    .touch-sale .sale-panel-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
    }

    .touch-sale .sale-panel-subtitle {
        margin: .15rem 0 0;
        color: var(--sale-muted);
        font-size: .82rem;
    }

    .touch-sale .sale-filters,
    .touch-sale .sale-customer-grid,
    .touch-sale .sale-doc-grid {
        display: grid;
        gap: .8rem;
    }

    .touch-sale .sale-doc-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        margin-bottom: 1rem;
    }

    .touch-sale .sale-customer-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .touch-sale .sale-filters {
        grid-template-columns: 1.4fr .8fr;
        padding: 1rem 1.1rem 1.1rem;
    }

    .touch-sale .sale-field label {
        display: block;
        margin-bottom: .35rem;
        font-size: .78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #516274;
    }

    .touch-sale .sale-field .form-control,
    .touch-sale .sale-field .custom-select {
        min-height: 48px;
        border-radius: 14px;
        border: 1px solid #cfd9e2;
        box-shadow: none;
        font-size: .96rem;
    }

    .touch-sale .sale-field .form-control:focus,
    .touch-sale .sale-field .custom-select:focus {
        border-color: var(--sale-cyan);
        box-shadow: 0 0 0 .2rem rgba(42, 167, 184, .15);
    }

    .touch-sale .sale-meta-card {
        background: var(--sale-soft);
        border: 1px dashed #cad6df;
        border-radius: 18px;
        padding: .95rem 1rem;
    }

    .touch-sale .sale-meta-card h6 {
        margin: 0 0 .65rem;
        font-weight: 700;
    }

    .touch-sale .sale-meta-list {
        display: grid;
        gap: .55rem;
    }

    .touch-sale .sale-meta-item small {
        display: block;
        color: var(--sale-muted);
        text-transform: uppercase;
        letter-spacing: .05em;
        font-size: .68rem;
    }

    .touch-sale .sale-meta-item strong {
        display: block;
        margin-top: .08rem;
        font-size: .94rem;
    }

    .touch-sale .sale-products-toolbar {
        display: flex;
        gap: .75rem;
        align-items: center;
        flex-wrap: wrap;
        padding: 1rem 1.1rem;
        border-bottom: 1px solid var(--sale-border);
    }

    .touch-sale .sale-search {
        flex: 1 1 320px;
        position: relative;
    }

    .touch-sale .sale-search .form-control {
        min-height: 50px;
        border-radius: 16px;
        padding-left: 2.8rem;
    }

    .touch-sale .sale-search i {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #7b8b9a;
    }

    .touch-sale .sale-filter-tags {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
    }

    .touch-sale .sale-filter-tag {
        border: 1px solid #cfd9e2;
        background: #fff;
        color: #415466;
        border-radius: 999px;
        padding: .5rem .9rem;
        font-weight: 700;
        font-size: .82rem;
        cursor: pointer;
    }

    .touch-sale .sale-filter-tag.active {
        background: var(--sale-navy);
        border-color: var(--sale-navy);
        color: #fff;
    }

    .touch-sale .sale-products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: .9rem;
        padding: 1rem 1.1rem 1.1rem;
        max-height: 62vh;
        overflow: auto;
    }

    .touch-sale .sale-product-card {
        border: 1px solid #d5e0e8;
        background: linear-gradient(180deg, #fff 0%, #f7fafc 100%);
        border-radius: 18px;
        padding: 1rem;
        min-height: 185px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
        cursor: pointer;
    }

    .touch-sale .sale-product-card:hover {
        transform: translateY(-2px);
        border-color: var(--sale-cyan);
        box-shadow: 0 14px 28px rgba(29, 91, 121, .12);
    }

    .touch-sale .sale-product-code {
        font-size: .74rem;
        font-weight: 800;
        color: var(--sale-blue);
        text-transform: uppercase;
        letter-spacing: .06em;
    }

    .touch-sale .sale-product-name {
        margin: .5rem 0 .7rem;
        font-size: .95rem;
        font-weight: 700;
        line-height: 1.35;
    }

    .touch-sale .sale-product-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .75rem;
        font-size: .82rem;
        color: var(--sale-muted);
    }

    .touch-sale .sale-product-price {
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--sale-ink);
    }

    .touch-sale .sale-product-stock.low {
        color: var(--sale-danger);
        font-weight: 700;
    }

    .touch-sale .sale-cart {
        position: sticky;
        top: 1rem;
    }

    .touch-sale .sale-cart-body {
        padding: 1rem 1.1rem 1.1rem;
    }

    .touch-sale .sale-cart-list {
        display: grid;
        gap: .85rem;
        max-height: 48vh;
        overflow: auto;
        padding-right: .2rem;
    }

    .touch-sale .sale-cart-empty {
        border: 2px dashed #d3dde5;
        border-radius: 18px;
        padding: 2rem 1rem;
        text-align: center;
        color: var(--sale-muted);
        background: #fbfdff;
    }

    .touch-sale .sale-cart-item {
        border: 1px solid #d9e2ea;
        border-radius: 18px;
        padding: .9rem;
        background: #fff;
    }

    .touch-sale .sale-cart-head {
        display: flex;
        justify-content: space-between;
        gap: .8rem;
        margin-bottom: .8rem;
    }

    .touch-sale .sale-cart-title {
        font-weight: 700;
        line-height: 1.35;
    }

    .touch-sale .sale-cart-code {
        font-size: .73rem;
        font-weight: 800;
        color: var(--sale-blue);
        text-transform: uppercase;
    }

    .touch-sale .sale-remove {
        border: 0;
        background: #fef2f2;
        color: var(--sale-danger);
        width: 38px;
        height: 38px;
        border-radius: 12px;
        font-size: 1rem;
    }

    .touch-sale .sale-cart-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .6rem;
    }

    .touch-sale .sale-cart-grid .form-control {
        min-height: 44px;
        border-radius: 12px;
        border: 1px solid #ced8e1;
        box-shadow: none;
        font-weight: 600;
    }

    .touch-sale .sale-cart-grid label {
        display: block;
        margin-bottom: .25rem;
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #718396;
        font-weight: 700;
    }

    .touch-sale .sale-cart-foot {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: .8rem;
        padding-top: .8rem;
        border-top: 1px dashed #d5dee6;
        font-size: .85rem;
    }

    .touch-sale .sale-cart-foot strong {
        font-size: 1rem;
        color: var(--sale-ink);
    }

    .touch-sale .sale-summary {
        margin-top: 1rem;
        padding: 1rem;
        border-radius: 20px;
        background: linear-gradient(180deg, #16324f 0%, #20486a 100%);
        color: #fff;
    }

    .touch-sale .sale-summary-row {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: .38rem 0;
        font-size: .92rem;
        color: rgba(255, 255, 255, .82);
    }

    .touch-sale .sale-summary-total {
        border-top: 1px solid rgba(255, 255, 255, .18);
        margin-top: .5rem;
        padding-top: .85rem;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 1rem;
    }

    .touch-sale .sale-summary-total strong {
        font-size: 2rem;
        line-height: 1;
        color: #fff;
    }

    .touch-sale .sale-actions {
        display: flex;
        gap: .75rem;
        margin-top: 1rem;
    }

    .touch-sale .sale-actions .btn {
        min-height: 52px;
        border-radius: 14px;
        font-weight: 700;
        flex: 1;
    }

    .touch-sale .sale-inline-note {
        color: var(--sale-muted);
        font-size: .8rem;
    }

    @media (max-width: 1199.98px) {
        .touch-sale .sale-doc-grid,
        .touch-sale .sale-customer-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 991.98px) {
        .touch-sale .sale-cart {
            position: static;
        }
    }

    @media (max-width: 767.98px) {
        .touch-sale .sale-doc-grid,
        .touch-sale .sale-customer-grid,
        .touch-sale .sale-cart-grid,
        .touch-sale .sale-kpis,
        .touch-sale .sale-filters {
            grid-template-columns: 1fr;
        }

        .touch-sale .sale-products-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .touch-sale .sale-main,
        .touch-sale .sale-topbar {
            padding: 1rem;
        }
    }
</style>
@endpush

<div class="touch-sale">
    <div class="sale-shell">
        <div class="sale-topbar">
            <div class="d-flex flex-wrap justify-content-between align-items-start">
                <div>
                    <div class="text-uppercase small" style="letter-spacing:.14em; opacity:.75;">Caisse tactile</div>
                    <h2 class="mb-1">{{ isset($document) ? 'Modifier la vente' : 'Nouvelle vente rapide' }}</h2>
                    <div class="sale-inline-note text-white-50">Selection tactile des articles, panier direct et total instantane.</div>
                </div>
                <div class="text-md-right mt-3 mt-md-0">
                    <div class="small text-uppercase" style="letter-spacing:.12em; opacity:.75;">Session</div>
                    <div class="h5 mb-0">{{ auth()->user()->name ?? 'Operateur' }}</div>
                </div>
            </div>

            <div class="sale-kpis">
                <div class="sale-kpi">
                    <div class="sale-kpi-label">Type</div>
                    <div class="sale-kpi-value" id="documentTypeBadge">{{ $types[old('type_document_code', $document->type_document_code ?? 'FA')] ?? 'Facture' }}</div>
                </div>
                <div class="sale-kpi">
                    <div class="sale-kpi-label">Client</div>
                    <div class="sale-kpi-value" id="headerCustomerName">{{ old('tier_id') ? 'Client selectionne' : 'Comptoir' }}</div>
                </div>
                <div class="sale-kpi">
                    <div class="sale-kpi-label">Articles</div>
                    <div class="sale-kpi-value"><span id="headerLineCount">{{ $initialCart->count() }}</span> ligne(s)</div>
                </div>
            </div>
        </div>

        <div class="sale-main">
            <div class="row">
                <div class="col-xl-8 mb-4 mb-xl-0">
                    <div class="sale-panel mb-4">
                        <div class="sale-panel-header">
                            <div>
                                <h3 class="sale-panel-title">En-tete de caisse</h3>
                                <p class="sale-panel-subtitle">Identification du ticket, client et livraison.</p>
                            </div>
                            <span class="badge badge-dark px-3 py-2" id="documentStatusBadge">{{ $statuts[old('do_expedition_statut', $document->do_expedition_statut ?? 'en_attente')] ?? 'En attente' }}</span>
                        </div>

                        <div class="p-3 p-md-4">
                            <div class="sale-doc-grid">
                                <div class="sale-field">
                                    <label for="do_piece">Numero ticket</label>
                                    <input type="text" name="do_piece" id="do_piece" class="form-control @error('do_piece') is-invalid @enderror"
                                        value="{{ old('do_piece', $document->do_piece ?? '') }}" required>
                                    @error('do_piece')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="sale-field">
                                    <label for="do_date">Date</label>
                                    <input type="date" name="do_date" id="do_date" class="form-control @error('do_date') is-invalid @enderror"
                                        value="{{ old('do_date', isset($document) ? optional($document->do_date)->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
                                    @error('do_date')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="sale-field">
                                    <label for="type_document_code">Type</label>
                                    <select name="type_document_code" id="type_document_code" class="custom-select @error('type_document_code') is-invalid @enderror" required>
                                        @foreach ($types as $value => $label)
                                            <option value="{{ $value }}" @selected(old('type_document_code', $document->type_document_code ?? 'FA') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('type_document_code')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="sale-field">
                                    <label for="do_expedition_statut">Statut</label>
                                    <select name="do_expedition_statut" id="do_expedition_statut" class="custom-select @error('do_expedition_statut') is-invalid @enderror" required>
                                        @foreach ($statuts as $value => $label)
                                            <option value="{{ $value }}" @selected(old('do_expedition_statut', $document->do_expedition_statut ?? 'en_attente') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('do_expedition_statut')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="sale-customer-grid">
                                <div class="sale-field">
                                    <label for="tier_id">Client / Tiers</label>
                                    <select name="tier_id" id="tier_id" class="custom-select @error('tier_id') is-invalid @enderror">
                                        <option value="">Vente comptoir</option>
                                        @foreach ($tiers as $tier)
                                            <option value="{{ $tier->id }}"
                                                data-code="{{ $tier->ct_num }}"
                                                data-tier-code="{{ $tier->code_tiers }}"
                                                data-name="{{ $tier->ct_intitule }}"
                                                data-phone="{{ $tier->ct_telephone }}"
                                                data-address="{{ $tier->ct_adresse }}"
                                                data-ice="{{ $tier->ct_ice }}"
                                                data-payment="{{ $tier->ct_delai_paiement }}"
                                                @selected((string) old('tier_id', $document->tier_id ?? '') === (string) $tier->id)>
                                                {{ $tier->code_tiers ?: $tier->ct_num }} - {{ $tier->ct_intitule }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('tier_id')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="sale-field">
                                    <label for="transporteur_id">Transporteur</label>
                                    <select name="transporteur_id" id="transporteur_id" class="custom-select @error('transporteur_id') is-invalid @enderror">
                                        <option value="">Aucun</option>
                                        @foreach ($transporteurs as $transporteur)
                                            <option value="{{ $transporteur->id }}" @selected(old('transporteur_id', $document->transporteur_id ?? '') == $transporteur->id)>
                                                {{ $transporteur->tr_nom }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('transporteur_id')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="sale-field">
                                    <label for="depot_id">Depot</label>
                                    <select name="depot_id" id="depot_id" class="custom-select @error('depot_id') is-invalid @enderror">
                                        <option value="">Defaut</option>
                                        @foreach ($depots as $depot)
                                            <option value="{{ $depot->id }}" @selected(old('depot_id', $document->depot_id ?? '') == $depot->id)>
                                                {{ $depot->intitule }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('depot_id')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="sale-meta-card">
                                    <h6>Fiche client</h6>
                                    <div class="sale-meta-list">
                                        <div class="sale-meta-item">
                                            <small>Code</small>
                                            <strong id="tierCode">-</strong>
                                        </div>
                                        <div class="sale-meta-item">
                                            <small>Nom</small>
                                            <strong id="tierName">Comptoir</strong>
                                        </div>
                                        <div class="sale-meta-item">
                                            <small>Telephone / ICE</small>
                                            <strong><span id="tierPhone">-</span> <span class="text-muted">|</span> <span id="tierIce">-</span></strong>
                                        </div>
                                        <div class="sale-meta-item">
                                            <small>Delai / Adresse</small>
                                            <strong><span id="tierPayment">-</span> <span class="text-muted">|</span> <span id="tierAddress">-</span></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="sale-doc-grid mt-3 mb-0">
                                <div class="sale-field">
                                    <label for="do_date_livraison">Date livraison</label>
                                    <input type="date" name="do_date_livraison" id="do_date_livraison" class="form-control @error('do_date_livraison') is-invalid @enderror"
                                        value="{{ old('do_date_livraison', isset($document) ? optional($document->do_date_livraison)->format('Y-m-d') : '') }}">
                                    @error('do_date_livraison')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="sale-field" style="grid-column: span 3;">
                                    <label for="do_lieu_livraison">Lieu de livraison</label>
                                    <input type="text" name="do_lieu_livraison" id="do_lieu_livraison" class="form-control @error('do_lieu_livraison') is-invalid @enderror"
                                        value="{{ old('do_lieu_livraison', $document->do_lieu_livraison ?? '') }}">
                                    @error('do_lieu_livraison')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sale-panel">
                        <div class="sale-panel-header">
                            <div>
                                <h3 class="sale-panel-title">Catalogue tactile</h3>
                                <p class="sale-panel-subtitle">Touchez un article pour l'ajouter directement au panier.</p>
                            </div>
                            <span class="badge badge-info px-3 py-2"><span id="visibleProductsCount">{{ $articleOptions->count() }}</span> article(s)</span>
                        </div>

                        <div class="sale-products-toolbar">
                            <div class="sale-search">
                                <i class="fas fa-search"></i>
                                <input type="text" id="productSearch" class="form-control" placeholder="Rechercher par code, designation ou code-barres">
                            </div>
                            <div class="sale-filter-tags">
                                <button type="button" class="sale-filter-tag active" data-filter="all">Tous</button>
                                <button type="button" class="sale-filter-tag" data-filter="low">Stock faible</button>
                                <button type="button" class="sale-filter-tag" data-filter="tracked">Suivi stock</button>
                            </div>
                        </div>

                        <div class="sale-products-grid" id="productGrid">
                            @foreach ($articleOptions as $article)
                                <button
                                    type="button"
                                    class="sale-product-card"
                                    data-article-id="{{ $article['id'] }}"
                                    data-code="{{ $article['code'] }}"
                                    data-design="{{ $article['design'] }}"
                                    data-barcode="{{ $article['barcode'] }}"
                                    data-price="{{ $article['price'] }}"
                                    data-tva="{{ $article['tva'] }}"
                                    data-unit="{{ $article['unit'] }}"
                                    data-stock="{{ $article['stock'] }}"
                                    data-stock-min="{{ $article['stock_min'] }}"
                                    data-track-stock="{{ $article['track_stock'] ? 1 : 0 }}"
                                >
                                    <div>
                                        <div class="sale-product-code">{{ $article['code'] }}</div>
                                        <div class="sale-product-name">{{ $article['design'] }}</div>
                                    </div>
                                    <div>
                                        <div class="sale-product-price">{{ number_format($article['price'], 2) }}</div>
                                        <div class="sale-product-meta">
                                            <span>{{ $article['unit'] }}</span>
                                            <span class="sale-product-stock {{ $article['track_stock'] && $article['stock'] <= $article['stock_min'] ? 'low' : '' }}">
                                                {{ $article['track_stock'] ? number_format($article['stock'], 3) : 'N/A' }}
                                            </span>
                                        </div>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="sale-panel sale-cart">
                        <div class="sale-panel-header">
                            <div>
                                <h3 class="sale-panel-title">Panier</h3>
                                <p class="sale-panel-subtitle">Ajustez quantite, prix et remise avant validation.</p>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="clearCartBtn">Vider</button>
                        </div>

                        <div class="sale-cart-body">
                            <div id="hiddenLinesContainer"></div>

                            <div id="cartList" class="sale-cart-list"></div>

                            <div class="sale-summary">
                                <div class="sale-summary-row">
                                    <span>Sous-total HT</span>
                                    <strong id="summaryHt">0.00</strong>
                                </div>
                                <div class="sale-summary-row">
                                    <span>TVA</span>
                                    <strong id="summaryTva">0.00</strong>
                                </div>
                                <div class="sale-summary-row">
                                    <span>Remise</span>
                                    <strong id="summaryDiscount">0.00</strong>
                                </div>
                                <div class="sale-summary-total">
                                    <div>
                                        <div class="small text-uppercase text-white-50">Total TTC</div>
                                        <strong id="summaryTtc">0.00</strong>
                                    </div>
                                    <div class="text-right">
                                        <div class="small text-uppercase text-white-50">Articles</div>
                                        <div class="h4 mb-0" id="totalLines">0</div>
                                    </div>
                                </div>
                            </div>

                            <div class="sale-actions">
                                <a href="{{ route('documents.index') }}" class="btn btn-light">Retour</a>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-save mr-1"></i> Valider la vente
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
    (() => {
        const articleOptions = @json($articleOptions);
        const articleMap = new Map(articleOptions.map(article => [String(article.id), article]));
        const initialCart = @json($initialCart);
        const typeLabels = @json($types);
        const statusLabels = @json($statuts);
        const isEditMode = @json(isset($document));

        const doPiece = document.getElementById('do_piece');
        const doDate = document.getElementById('do_date');
        const doType = document.getElementById('type_document_code');
        const doStatus = document.getElementById('do_expedition_statut');
        const tierField = document.getElementById('tier_id');
        const cartList = document.getElementById('cartList');
        const hiddenLinesContainer = document.getElementById('hiddenLinesContainer');
        const productSearch = document.getElementById('productSearch');
        const productGrid = document.getElementById('productGrid');
        const clearCartBtn = document.getElementById('clearCartBtn');
        const totalLines = document.getElementById('totalLines');
        const headerLineCount = document.getElementById('headerLineCount');
        const summaryHt = document.getElementById('summaryHt');
        const summaryTva = document.getElementById('summaryTva');
        const summaryTtc = document.getElementById('summaryTtc');
        const summaryDiscount = document.getElementById('summaryDiscount');
        const visibleProductsCount = document.getElementById('visibleProductsCount');
        const documentTypeBadge = document.getElementById('documentTypeBadge');
        const documentStatusBadge = document.getElementById('documentStatusBadge');
        const headerCustomerName = document.getElementById('headerCustomerName');
        const tierCode = document.getElementById('tierCode');
        const tierName = document.getElementById('tierName');
        const tierPhone = document.getElementById('tierPhone');
        const tierIce = document.getElementById('tierIce');
        const tierPayment = document.getElementById('tierPayment');
        const tierAddress = document.getElementById('tierAddress');

        let cart = Array.isArray(initialCart) ? initialCart.map(item => ({
            article_id: Number(item.article_id),
            dl_qte: Number(item.dl_qte || 1),
            dl_prix_unitaire_ht: Number(item.dl_prix_unitaire_ht || 0),
            dl_remise_percent: Number(item.dl_remise_percent || 0),
        })) : [];

        let pieceTouched = doPiece.value.trim() !== '';
        let activeFilter = 'all';

        function formatMoney(value) {
            return Number(value || 0).toFixed(2);
        }

        function normalizeTypeLabel(value) {
            return (typeLabels[value] || 'DOC')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^A-Za-z0-9]+/g, '')
                .toUpperCase()
                .slice(0, 4) || 'DOC';
        }

        function syncSuggestedPiece() {
            if (isEditMode || pieceTouched) {
                return;
            }

            const dateValue = doDate.value || new Date().toISOString().slice(0, 10);
            const compactDate = dateValue.replaceAll('-', '');
            doPiece.value = `${normalizeTypeLabel(doType.value)}-${compactDate}`;
        }

        function getArticle(articleId) {
            return articleMap.get(String(articleId));
        }

        function getLineTotals(line) {
            const article = getArticle(line.article_id);
            const qty = Number(line.dl_qte || 0);
            const price = Number(line.dl_prix_unitaire_ht || 0);
            const discount = Math.max(0, Math.min(100, Number(line.dl_remise_percent || 0)));
            const rate = Number(article?.tva || 0);
            const gross = qty * price;
            const discountAmount = gross * (discount / 100);
            const totalHt = gross - discountAmount;
            const totalTva = totalHt * (rate / 100);
            const totalTtc = totalHt + totalTva;

            return { qty, price, discount, rate, totalHt, totalTva, totalTtc, discountAmount };
        }

        function updateTierPanel() {
            const option = tierField.selectedOptions[0];

            if (!option || !option.value) {
                tierCode.textContent = '-';
                tierName.textContent = 'Comptoir';
                tierPhone.textContent = '-';
                tierIce.textContent = '-';
                tierPayment.textContent = '-';
                tierAddress.textContent = '-';
                headerCustomerName.textContent = 'Comptoir';
                return;
            }

            tierCode.textContent = option.dataset.tierCode || option.dataset.code || '-';
            tierName.textContent = option.dataset.name || '-';
            tierPhone.textContent = option.dataset.phone || '-';
            tierIce.textContent = option.dataset.ice || '-';
            tierPayment.textContent = option.dataset.payment ? `${option.dataset.payment} j` : '-';
            tierAddress.textContent = option.dataset.address || '-';
            headerCustomerName.textContent = option.dataset.name || 'Client selectionne';
        }

        function renderHiddenInputs() {
            hiddenLinesContainer.innerHTML = '';

            cart.forEach((line, index) => {
                ['article_id', 'dl_qte', 'dl_prix_unitaire_ht', 'dl_remise_percent'].forEach(field => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `lines[${index}][${field}]`;
                    input.value = line[field];
                    hiddenLinesContainer.appendChild(input);
                });
            });
        }

        function renderCart() {
            if (!cart.length) {
                cartList.innerHTML = `
                    <div class="sale-cart-empty">
                        <div class="mb-2"><i class="fas fa-shopping-basket fa-2x text-muted"></i></div>
                        <div class="font-weight-bold mb-1">Aucun article dans le panier</div>
                        <div class="small">Touchez un produit a gauche pour commencer la vente.</div>
                    </div>
                `;
                renderHiddenInputs();
                updateSummary();
                return;
            }

            cartList.innerHTML = cart.map((line, index) => {
                const article = getArticle(line.article_id);
                const totals = getLineTotals(line);

                if (!article) {
                    return '';
                }

                return `
                    <div class="sale-cart-item" data-index="${index}">
                        <div class="sale-cart-head">
                            <div>
                                <div class="sale-cart-code">${article.code}</div>
                                <div class="sale-cart-title">${article.design}</div>
                            </div>
                            <button type="button" class="sale-remove" data-action="remove" data-index="${index}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>

                        <div class="sale-cart-grid">
                            <div>
                                <label>Qte</label>
                                <input type="number" min="0.001" step="0.001" class="form-control cart-input" data-field="dl_qte" data-index="${index}" value="${totals.qty}">
                            </div>
                            <div>
                                <label>Prix HT</label>
                                <input type="number" min="0" step="0.00001" class="form-control cart-input" data-field="dl_prix_unitaire_ht" data-index="${index}" value="${totals.price}">
                            </div>
                            <div>
                                <label>Remise %</label>
                                <input type="number" min="0" max="100" step="0.01" class="form-control cart-input" data-field="dl_remise_percent" data-index="${index}" value="${totals.discount}">
                            </div>
                        </div>

                        <div class="sale-cart-foot">
                            <div>
                                <div class="small text-muted">${formatMoney(totals.rate)}% TVA</div>
                                <strong>${formatMoney(totals.totalTtc)}</strong>
                            </div>
                            <div class="text-right">
                                <div class="small text-muted">Stock ${article.track_stock ? formatMoney(article.stock) : 'N/A'}</div>
                                <div class="font-weight-bold">${article.unit}</div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            renderHiddenInputs();
            updateSummary();
        }

        function updateSummary() {
            let totalHtValue = 0;
            let totalTvaValue = 0;
            let totalTtcValue = 0;
            let totalDiscountValue = 0;

            cart.forEach(line => {
                const totals = getLineTotals(line);
                totalHtValue += totals.totalHt;
                totalTvaValue += totals.totalTva;
                totalTtcValue += totals.totalTtc;
                totalDiscountValue += totals.discountAmount;
            });

            totalLines.textContent = cart.length;
            headerLineCount.textContent = cart.length;
            summaryHt.textContent = formatMoney(totalHtValue);
            summaryTva.textContent = formatMoney(totalTvaValue);
            summaryTtc.textContent = formatMoney(totalTtcValue);
            summaryDiscount.textContent = formatMoney(totalDiscountValue);
        }

        function addArticleToCart(articleId) {
            const article = getArticle(articleId);

            if (!article) {
                return;
            }

            const existing = cart.find(line => Number(line.article_id) === Number(articleId));

            if (existing) {
                existing.dl_qte = Number(existing.dl_qte) + 1;
            } else {
                cart.unshift({
                    article_id: Number(articleId),
                    dl_qte: 1,
                    dl_prix_unitaire_ht: Number(article.price || 0),
                    dl_remise_percent: 0,
                });
            }

            renderCart();
        }

        function filterProducts() {
            const term = (productSearch.value || '').trim().toLowerCase();
            let visibleCount = 0;

            productGrid.querySelectorAll('.sale-product-card').forEach(card => {
                const haystack = [
                    card.dataset.code,
                    card.dataset.design,
                    card.dataset.barcode,
                ].join(' ').toLowerCase();

                const stock = Number(card.dataset.stock || 0);
                const stockMin = Number(card.dataset.stockMin || 0);
                const tracked = card.dataset.trackStock === '1';

                let matchesFilter = true;

                if (activeFilter === 'low') {
                    matchesFilter = tracked && stock <= stockMin;
                } else if (activeFilter === 'tracked') {
                    matchesFilter = tracked;
                }

                const visible = haystack.includes(term) && matchesFilter;
                card.style.display = visible ? '' : 'none';

                if (visible) {
                    visibleCount++;
                }
            });

            visibleProductsCount.textContent = visibleCount;
        }

        function updateHeaderBadges() {
            documentTypeBadge.textContent = typeLabels[doType.value] || 'Document';
            documentStatusBadge.textContent = statusLabels[doStatus.value] || 'En attente';
        }

        productGrid.addEventListener('click', event => {
            const card = event.target.closest('.sale-product-card');

            if (!card) {
                return;
            }

            addArticleToCart(card.dataset.articleId);
        });

        cartList.addEventListener('input', event => {
            const input = event.target.closest('.cart-input');

            if (!input) {
                return;
            }

            const index = Number(input.dataset.index);
            const field = input.dataset.field;

            if (!cart[index]) {
                return;
            }

            cart[index][field] = Number(input.value || 0);
            renderHiddenInputs();
            updateSummary();
        });

        cartList.addEventListener('click', event => {
            const removeButton = event.target.closest('[data-action="remove"]');

            if (!removeButton) {
                return;
            }

            const index = Number(removeButton.dataset.index);
            cart.splice(index, 1);
            renderCart();
        });

        clearCartBtn.addEventListener('click', () => {
            cart = [];
            renderCart();
        });

        document.querySelectorAll('.sale-filter-tag').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.sale-filter-tag').forEach(item => item.classList.remove('active'));
                button.classList.add('active');
                activeFilter = button.dataset.filter;
                filterProducts();
            });
        });

        productSearch.addEventListener('input', filterProducts);
        tierField.addEventListener('change', updateTierPanel);

        doPiece.addEventListener('input', () => {
            pieceTouched = doPiece.value.trim() !== '';
        });

        doDate.addEventListener('change', syncSuggestedPiece);
        doType.addEventListener('change', () => {
            syncSuggestedPiece();
            updateHeaderBadges();
        });
        doStatus.addEventListener('change', updateHeaderBadges);

        syncSuggestedPiece();
        updateTierPanel();
        updateHeaderBadges();
        filterProducts();
        renderCart();
    })();
</script>
@endpush
