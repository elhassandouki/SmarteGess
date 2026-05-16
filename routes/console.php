<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:import-gcom {--source=tajhiadk_gestion}', function () {
    $source = (string) $this->option('source');

    $this->components->info("Importing business data from [{$source}] into [gestion]...");

    $legacyDoTypeFromCode = static function (string $code): int {
        return match ($code) {
            'BL' => 2,
            'FA', 'FF' => 3,
            'BR', 'FR', 'MV', 'AJ', 'TR' => 4,
            default => 1,
        };
    };

    $resolveModule = static function (?string $raw): string {
        $value = mb_strtolower(trim((string) $raw));

        return match (true) {
            str_contains($value, 'achat') => 'purchase',
            str_contains($value, 'stock'),
            str_contains($value, 'invent') => 'stock',
            default => 'sales',
        };
    };

    $resolveTypeCode = static function (string $module, ?string $piece): string {
        $pieceCode = strtoupper(trim((string) $piece));

        if ($module === 'purchase') {
            return match ($pieceCode) {
                'FA', 'FF' => 'FF',
                default => 'BA',
            };
        }

        if ($module === 'stock') {
            return match ($pieceCode) {
                'TR' => 'TR',
                'AJ' => 'AJ',
                default => 'MV',
            };
        }

        return match ($pieceCode) {
            'DE', 'BC', 'BL', 'FA', 'BR', 'FR' => $pieceCode,
            default => 'BC',
        };
    };

    $workflowFromCode = static function (string $code): string {
        return match ($code) {
            'DE' => 'quote',
            'BC', 'BA' => 'order',
            'BL' => 'delivery',
            'FA', 'FF' => 'invoice',
            'BR' => 'return',
            'FR' => 'credit_note',
            'AJ' => 'adjustment',
            'TR' => 'transfer',
            default => 'movement',
        };
    };

    $fluxFromModule = static function (string $module): string {
        return match ($module) {
            'purchase' => 'achat',
            'stock' => 'stock',
            default => 'vente',
        };
    };

    $quantityColumnMap = [
        'BC' => 'qte_BC',
        'BL' => 'qte_BL',
        'FA' => 'qte_FA',
        'BR' => 'qte_BR',
        'FR' => 'qte_FR',
        'DE' => 'qte_DE',
        'MS' => 'qte_MS',
    ];

    $statusFromDocument = static function (object $document): string {
        if (! empty($document->date_livraison)) {
            return 'livre';
        }

        return match ($document->etat) {
            'validé' => 'en_cours',
            default => 'en_attente',
        };
    };

    $makePiece = static function (object $document): string {
        $candidates = [
            $document->ref,
            $document->num_de,
            $document->num_bc,
            $document->num_bl,
            $document->num_br,
            $document->num_fa,
            $document->num_fr,
            $document->num_ms,
            $document->ref_de,
            $document->ref_bc,
            $document->ref_bl,
            $document->ref_br,
            $document->ref_fa,
            $document->ref_fr,
            $document->ref_ms,
        ];

        foreach ($candidates as $candidate) {
            if (! empty($candidate)) {
                return (string) $candidate;
            }
        }

        return 'DOC-'.$document->id;
    };

    $makeUniqueValue = static function (?string $base, int|string $id, array &$used, string $prefix): string {
        $value = trim((string) $base);
        $value = $value !== '' ? $value : $prefix.'-'.$id;

        if (! isset($used[$value])) {
            $used[$value] = 1;

            return $value;
        }

        $used[$value]++;

        return $value.'-'.$id;
    };

    $mapPaymentMode = static function (?string $mode): int {
        $normalized = mb_strtolower(trim((string) $mode));

        return match (true) {
            str_contains($normalized, 'ch') => 2,
            str_contains($normalized, 'vir') => 3,
            str_contains($normalized, 'eff') || str_contains($normalized, 'traite') => 4,
            default => 1,
        };
    };

    $now = now();
    $docColumns = Schema::getColumnListing('f_docentete');
    $hasTypeCode = in_array('type_document_code', $docColumns, true);
    $hasDocModule = in_array('doc_module', $docColumns, true);
    $hasWorkflowType = in_array('workflow_type', $docColumns, true);
    $hasFluxType = in_array('flux_type', $docColumns, true);
    $hasDepotIdOnDoc = in_array('depot_id', $docColumns, true);

    DB::statement('SET FOREIGN_KEY_CHECKS=0');

    if (Schema::hasTable('f_reglements')) {
        DB::table('f_reglements')->truncate();
    }
    if (Schema::hasTable('f_comptet')) {
        DB::table('f_comptet')->truncate();
    }
    DB::table('f_docligne')->truncate();
    DB::table('f_docentete')->truncate();
    DB::table('f_transporteurs')->truncate();
    DB::table('f_articles')->truncate();
    DB::table('f_familles')->truncate();
    if (Schema::hasTable('f_stock')) {
        DB::table('f_stock')->truncate();
    }

    $principalDepotId = null;
    if (Schema::hasTable('f_depots')) {
        DB::table('f_depots')->updateOrInsert(
            ['id' => 1],
            [
                'code_depot' => 'DEPOT-PRINCIPAL',
                'intitule' => 'Depot principal',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
        $principalDepotId = 1;
    }

    $isTajhiz = in_array($source, ['bd_tajhiz', 'bd_tajhiz25', 'tajhiadk_gestion'], true);
    $stockByArticle = collect();

    if ($isTajhiz) {
        $families = collect(DB::table("{$source}.famille_article")->orderBy('id')->get());

        if ($families->isNotEmpty()) {
            DB::table('f_familles')->insert(
                $families->map(fn (object $family) => [
                    'id' => $family->id,
                    'fa_code' => $family->code ?: 'FAM-'.$family->id,
                    'fa_intitule' => $family->famille,
                    'created_at' => $family->created_at ?? $now,
                    'updated_at' => $family->updated_at ?? $now,
                ])->all()
            );
        }

        $familyIdByName = $families
            ->mapWithKeys(fn (object $family) => [mb_strtolower(trim($family->famille)) => $family->id]);

        $articles = collect(DB::table("{$source}.articles")->orderBy('id')->get());
        $usedArticleRefs = [];

        if ($articles->isNotEmpty()) {
            $articleRows = [];

            foreach ($articles as $article) {
                $familyName = mb_strtolower(trim((string) $article->famille));

                $articleRows[] = [
                    'id' => $article->id,
                    'ar_ref' => $makeUniqueValue($article->ref ?: $article->code, $article->id, $usedArticleRefs, 'ART'),
                    'ar_design' => $article->designation ?: 'Article '.$article->id,
                    'family_id' => $familyIdByName[$familyName] ?? null,
                    'ar_prix_achat' => $article->prix_achat ?? 0,
                    'ar_prix_vente' => $article->prix_vente ?? 0,
                    'ar_stock_actuel' => $article->qte ?? 0,
                    'ar_unite' => $article->unite ?: 'Pcs',
                    'created_at' => $article->created_at ?? $now,
                    'updated_at' => $article->updated_at ?? $now,
                ];
            }

            DB::table('f_articles')->insert($articleRows);
        }

        $transporteurs = collect(DB::table("{$source}.doc")
            ->whereNotNull('livreur')
            ->where('livreur', '!=', '')
            ->select('livreur')
            ->distinct()
            ->orderBy('livreur')
            ->get())
            ->values();

        $transporteurRows = $transporteurs->map(fn (object $row, int $index) => [
            'id' => $index + 1,
            'tr_nom' => trim($row->livreur),
            'tr_matricule' => null,
            'tr_chauffeur' => null,
            'tr_telephone' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($transporteurRows !== []) {
            DB::table('f_transporteurs')->insert($transporteurRows);
        }

        $transporteurIdByName = collect($transporteurRows)->mapWithKeys(
            fn (array $row) => [mb_strtolower(trim($row['tr_nom'])) => $row['id']]
        );

        $documents = collect(DB::table("{$source}.doc")->orderBy('id')->get());
        $comptets = $documents
            ->filter(fn (object $document) => ! empty($document->code_tiers) || ! empty($document->tiers))
            ->map(function (object $document) {
                return (object) [
                    'ct_num' => trim((string) ($document->code_tiers ?: $document->tiers)),
                    'ct_intitule' => trim((string) ($document->tiers ?: $document->code_tiers)),
                    'ct_type' => $document->type === 'achats' ? 'fournisseur' : 'client',
                ];
            })
            ->unique('ct_num')
            ->values();

        if (Schema::hasTable('f_comptet') && $comptets->isNotEmpty()) {
            DB::table('f_comptet')->insert(
                $comptets->map(fn (object $compte) => [
                    'ct_num' => $compte->ct_num,
                    'ct_intitule' => $compte->ct_intitule,
                    'ct_type' => $compte->ct_type,
                    'ct_ice' => null,
                    'ct_if' => null,
                    'ct_encours_max' => 0,
                    'ct_delai_paiement' => 0,
                    'ct_telephone' => null,
                    'ct_adresse' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );
        }

        $tierIdByNum = Schema::hasTable('f_comptet')
            ? DB::table('f_comptet')->pluck('id', 'ct_num')
            : collect();
        $usedPieces = [];

        $documentRows = [];

        foreach ($documents as $document) {
            $amountTtc = (float) ($document->montant_ttc ?? 0);
            $amountReste = (float) ($document->montant_reste ?? 0);
            $amountRegle = max(0, $amountTtc - $amountReste);

            $row = [
                'id' => $document->id,
                'do_piece' => $makeUniqueValue($document->n_piece ?: $document->ref, $document->id, $usedPieces, 'DOC'),
                'do_date' => $document->date ?? $now->toDateString(),
                'tier_id' => $tierIdByNum[trim((string) ($document->code_tiers ?: $document->tiers))] ?? null,
                'do_type' => $legacyDoTypeFromCode($resolveTypeCode(
                    $resolveModule($document->type ?? null),
                    $document->type_piece ?? null
                )),
                'transporteur_id' => ! empty($document->livreur)
                    ? ($transporteurIdByName[mb_strtolower(trim($document->livreur))] ?? null)
                    : null,
                'do_lieu_livraison' => $document->tiers ?: null,
                'do_date_livraison' => $document->date_livraison,
                'do_expedition_statut' => ! empty($document->date_livraison) ? 'livre' : 'en_attente',
                'do_total_ht' => $document->montant_ht ?? 0,
                'do_total_tva' => $document->montant_tva ?? 0,
                'do_total_ttc' => $amountTtc,
                'do_montant_regle' => $amountRegle,
                'do_statut' => $amountRegle >= $amountTtc && $amountTtc > 0 ? 2 : ($amountRegle > 0 ? 1 : 0),
                'created_at' => $document->created_at ?? $now,
                'updated_at' => $document->updated_at ?? $now,
            ];

            $module = $resolveModule($document->type ?? null);
            $typeCode = $resolveTypeCode($module, $document->type_piece ?? null);
            if ($hasTypeCode) {
                $row['type_document_code'] = $typeCode;
            }
            if ($hasDocModule) {
                $row['doc_module'] = $module;
            }
            if ($hasWorkflowType) {
                $row['workflow_type'] = $workflowFromCode($typeCode);
            }
            if ($hasFluxType) {
                $row['flux_type'] = $fluxFromModule($module);
            }
            if ($hasDepotIdOnDoc && $principalDepotId !== null && $module === 'stock') {
                $row['depot_id'] = $principalDepotId;
            }

            $documentRows[] = $row;
        }

        if ($documentRows !== []) {
            DB::table('f_docentete')->insert($documentRows);
        }

        $articleIdByRef = [];
        foreach ($articles as $article) {
            $refKey = trim((string) $article->ref);
            if ($refKey !== '' && ! isset($articleIdByRef[$refKey])) {
                $articleIdByRef[$refKey] = $article->id;
            }
        }
        $articleIdByCode = $articles->mapWithKeys(fn (object $article) => [trim($article->code) => $article->id]);
        $validDocumentIds = $documents->pluck('id')->map(fn ($id) => (string) $id)->flip();
        $sourceLines = collect(DB::table("{$source}.ligne_doc")->orderBy('id')->get());
        $lineRows = [];

        foreach ($sourceLines as $line) {
            $docId = trim((string) $line->doc_id);

            if (! isset($validDocumentIds[$docId])) {
                continue;
            }

            $articleId = $articleIdByRef[trim((string) $line->ref)] ?? $articleIdByCode[trim((string) $line->code)] ?? null;

            if (! $articleId) {
                continue;
            }

            $lineRows[] = [
                'id' => $line->id,
                'doc_id' => (int) $docId,
                'article_id' => $articleId,
                'dl_qte' => $line->qte > 0 ? $line->qte : 1,
                'dl_prix_unitaire_ht' => $line->prix_unite ?? 0,
                'dl_prix_revient' => $line->tarif_net ?? ($line->prix_unite ?? 0),
                'dl_remise_percent' => $line->remise ?? 0,
                'dl_montant_ht' => $line->montant_ht ?? 0,
                'dl_montant_ttc' => $line->montant_ttc ?? (($line->qte ?: 1) * ($line->prix_unite ?? 0)),
                'created_at' => $line->created_at ?? $now,
                'updated_at' => $line->updated_at ?? $now,
            ];
        }

        if (Schema::hasTable('f_reglements')) {
            $reglements = collect(DB::table("{$source}.reglement")->orderBy('id')->get());

            if ($reglements->isNotEmpty()) {
                DB::table('f_reglements')->insert(
                    $reglements->map(function (object $reglement) use ($documents, $tierIdByNum, $mapPaymentMode, $now) {
                        $document = $documents->firstWhere('id', (int) $reglement->doc_id);
                        $tierCode = trim((string) ($reglement->code_tiers ?: $reglement->tiers));

                        return [
                            'id' => $reglement->id,
                            'doc_id' => $document?->id,
                            'tier_id' => $tierIdByNum[$tierCode] ?? null,
                            'rg_date' => $reglement->date ?? optional($document?->date)->format('Y-m-d') ?? $now->toDateString(),
                            'rg_libelle' => $reglement->libelle ?: $reglement->commentaire,
                            'rg_montant' => $reglement->montant_paye ?? $reglement->montant_av ?? 0,
                            'rg_mode_reglement' => $mapPaymentMode($reglement->mode),
                            'rg_reference' => null,
                            'rg_date_echeance' => null,
                            'rg_banque' => $reglement->banque,
                            'rg_valide' => mb_strtolower((string) $reglement->etat) !== 'en cours',
                            'created_at' => $reglement->created_at ?? $now,
                            'updated_at' => $reglement->updated_at ?? $now,
                        ];
                    })->filter(fn (array $row) => ! empty($row['tier_id']))->values()->all()
                );
            }
        }
    } else {
        $families = collect(DB::table("{$source}.familles_articles")
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get());

        if ($families->isNotEmpty()) {
            DB::table('f_familles')->insert(
                $families->map(fn (object $family) => [
                    'id' => $family->id,
                    'fa_code' => $family->code,
                    'fa_intitule' => $family->famille,
                    'created_at' => $family->created_at ?? $now,
                    'updated_at' => $family->updated_at ?? $now,
                ])->all()
            );
        }

        $stockByArticle = DB::table("{$source}.historique_stock")
            ->selectRaw('article_id, COALESCE(SUM(qte), 0) as total_qte')
            ->whereNull('deleted_at')
            ->groupBy('article_id')
            ->pluck('total_qte', 'article_id');

        $articles = collect(DB::table("{$source}.articles")
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get());
        $usedArticleRefs = [];

        if ($articles->isNotEmpty()) {
            $articleRows = [];

            foreach ($articles as $article) {
                $articleRows[] = [
                    'id' => $article->id,
                    'ar_ref' => $makeUniqueValue($article->ref ?: $article->code, $article->id, $usedArticleRefs, 'ART'),
                    'ar_design' => $article->designation ?: 'Article '.$article->id,
                    'ar_code_barre' => null,
                    'family_id' => $article->famille_id,
                    'ar_prix_achat' => $article->prix_achat ?? 0,
                    'ar_prix_vente' => $article->prix_vente ?? 0,
                    'ar_prix_revient' => $article->prix_achat ?? 0,
                    'ar_tva' => 20,
                    'ar_stock_min' => 0,
                    'ar_stock_actuel' => (float) ($stockByArticle[$article->id] ?? 0),
                    'ar_suivi_stock' => true,
                    'ar_unite' => 'Pcs',
                    'created_at' => $article->created_at ?? $now,
                    'updated_at' => $article->updated_at ?? $now,
                ];
            }

            DB::table('f_articles')->insert($articleRows);
        }

        $expeditionNames = DB::table("{$source}.shared_values")
            ->where('type', 'expedition_name')
            ->pluck('value', 'id');

        $expeditions = collect(DB::table("{$source}.expedition")
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get());

        if ($expeditions->isNotEmpty()) {
            DB::table('f_transporteurs')->insert(
                $expeditions->map(function (object $expedition) use ($expeditionNames, $now) {
                    $name = $expedition->transporteur
                        ?: ($expeditionNames[$expedition->expedition_name_id] ?? null)
                        ?: 'Transporteur '.$expedition->id;

                    return [
                        'id' => $expedition->id,
                        'tr_nom' => $name,
                        'tr_matricule' => $expedition->numero_suivi,
                        'tr_chauffeur' => null,
                        'tr_telephone' => null,
                        'created_at' => $expedition->created_at ?? $now,
                        'updated_at' => $expedition->updated_at ?? $now,
                    ];
                })->all()
            );
        }

        $documents = collect(DB::table("{$source}.doc")
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get());
        if (Schema::hasTable('f_comptet')) {
            $tiers = collect(DB::table("{$source}.tiers")->whereNull('deleted_at')->orderBy('id')->get());

            if ($tiers->isNotEmpty()) {
                DB::table('f_comptet')->insert(
                    $tiers->map(fn (object $tier) => [
                        'id' => $tier->id,
                        'ct_num' => $tier->code ?: 'CT-'.$tier->id,
                        'ct_intitule' => $tier->nom ?: 'Compte '.$tier->id,
                        'ct_type' => 'client',
                        'ct_ice' => null,
                        'ct_if' => null,
                        'ct_encours_max' => 0,
                        'ct_delai_paiement' => 0,
                        'ct_telephone' => $tier->telephone,
                        'ct_adresse' => $tier->adresse,
                        'created_at' => $tier->created_at ?? $now,
                        'updated_at' => $tier->updated_at ?? $now,
                    ])->all()
                );
            }
        }
        $usedPieces = [];

        $documentRows = [];

        foreach ($documents as $document) {
            $amountTtc = (float) ($document->total_ttc ?? 0);
            $row = [
                'id' => $document->id,
                'do_piece' => $makeUniqueValue($makePiece($document), $document->id, $usedPieces, 'DOC'),
                'do_date' => $document->date ?? $now->toDateString(),
                'tier_id' => $document->tiers_id,
                'do_type' => $legacyDoTypeFromCode($resolveTypeCode(
                    $resolveModule($document->type ?? null),
                    $document->type_piece ?? null
                )),
                'transporteur_id' => $document->expedition_id,
                'do_lieu_livraison' => null,
                'do_date_livraison' => $document->date_livraison,
                'do_expedition_statut' => $statusFromDocument($document),
                'do_total_ht' => $document->total_ht ?? 0,
                'do_total_tva' => $document->total_tva ?? 0,
                'do_total_ttc' => $amountTtc,
                'do_montant_regle' => 0,
                'do_statut' => 0,
                'created_at' => $document->created_at ?? $now,
                'updated_at' => $document->updated_at ?? $now,
            ];

            $module = $resolveModule($document->type ?? null);
            $typeCode = $resolveTypeCode($module, $document->type_piece ?? null);
            if ($hasTypeCode) {
                $row['type_document_code'] = $typeCode;
            }
            if ($hasDocModule) {
                $row['doc_module'] = $module;
            }
            if ($hasWorkflowType) {
                $row['workflow_type'] = $workflowFromCode($typeCode);
            }
            if ($hasFluxType) {
                $row['flux_type'] = $fluxFromModule($module);
            }
            if ($hasDepotIdOnDoc && $principalDepotId !== null && $module === 'stock') {
                $row['depot_id'] = $principalDepotId;
            }

            $documentRows[] = $row;
        }

        $sourceLines = collect(DB::table("{$source}.ligne_doc")
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get());

        $validArticleIds = $articles->pluck('id')->flip();
        $documentsById = $documents->keyBy('id');
        $lineRows = [];

        foreach ($sourceLines as $line) {
            if (! isset($validArticleIds[$line->article_id]) || ! isset($documentsById[$line->doc_id])) {
                continue;
            }

            $docType = $documentsById[$line->doc_id]->type;
            $quantityColumn = $quantityColumnMap[$docType] ?? 'qte_BC';
            $quantity = (float) ($line->{$quantityColumn} ?? 0);

            if ($quantity <= 0) {
                $quantity = 1;
            }

            $amount = (float) ($line->montant_ttc ?? 0);

            if ($amount <= 0) {
                $amount = $quantity * (float) ($line->prix_unite ?? 0);
            }

            $lineRows[] = [
                'id' => $line->id,
                'doc_id' => $line->doc_id,
                'article_id' => $line->article_id,
                'dl_qte' => $quantity,
                'dl_prix_unitaire_ht' => $line->prix_unite ?? 0,
                'dl_prix_revient' => $line->prix_unite ?? 0,
                'dl_remise_percent' => 0,
                'dl_montant_ht' => $line->montant_ht ?? $amount,
                'dl_montant_ttc' => $amount,
                'created_at' => $line->created_at ?? $now,
                'updated_at' => $line->updated_at ?? $now,
            ];
        }
    }

    if ($lineRows !== []) {
        DB::table('f_docligne')->insert($lineRows);
    }

    if (Schema::hasTable('f_stock') && $principalDepotId !== null && $articles->isNotEmpty()) {
        $stockRows = $articles->map(function (object $article) use ($isTajhiz, $stockByArticle, $principalDepotId, $now) {
            $qty = $isTajhiz
                ? (float) ($article->qte ?? 0)
                : (float) ($stockByArticle[$article->id] ?? 0);

            return [
                'article_id' => $article->id,
                'depot_id' => $principalDepotId,
                'stock_reel' => $qty,
                'stock_reserve' => 0,
                'created_at' => $article->created_at ?? $now,
                'updated_at' => $article->updated_at ?? $now,
            ];
        })->all();

        if ($stockRows !== []) {
            DB::table('f_stock')->insert($stockRows);
        }
    }

    DB::statement('SET FOREIGN_KEY_CHECKS=1');

    $this->newLine();
    $this->components->info('Import completed successfully.');
    $this->table(
        ['Table', 'Imported rows'],
        [
            ['f_familles', count($families)],
            ['f_articles', count($articles)],
            ['f_comptet', Schema::hasTable('f_comptet') ? DB::table('f_comptet')->count() : 0],
            ['f_transporteurs', $isTajhiz ? count($transporteurRows) : count($expeditions)],
            ['f_docentete', count($documentRows)],
            ['f_docligne', count($lineRows)],
            ['f_stock', Schema::hasTable('f_stock') ? DB::table('f_stock')->count() : 0],
            ['f_reglements', Schema::hasTable('f_reglements') ? DB::table('f_reglements')->count() : 0],
        ]
    );
})->purpose('Import business data from bd_gcom into the current application schema');
