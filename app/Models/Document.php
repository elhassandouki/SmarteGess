<?php

namespace App\Models;

use App\Support\DocumentTypeRegistry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Document extends Model
{
    use HasFactory;

    protected $table = 'f_docentete';

    protected $fillable = [
        'do_piece',
        'do_date',
        'tier_id',
        'do_type',
        'type_document_code',
        'flux_type',
        'doc_module',
        'workflow_type',
        'lifecycle_status',
        'posted_at',
        'cancelled_at',
        'depot_id',
        'transporteur_id',
        'do_lieu_livraison',
        'do_date_livraison',
        'do_expedition_statut',
        'do_total_ht',
        'do_total_tva',
        'do_total_ttc',
        'do_montant_regle',
        'do_statut',
    ];

    protected function casts(): array
    {
        return [
            'do_date' => 'date',
            'do_date_livraison' => 'date',
            'posted_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'do_total_ht' => 'decimal:2',
            'do_total_tva' => 'decimal:2',
            'do_total_ttc' => 'decimal:2',
            'do_montant_regle' => 'decimal:2',
            'do_statut' => 'integer',
        ];
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(CompteT::class, 'tier_id');
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class, 'depot_id');
    }

    public function transporteur(): BelongsTo
    {
        return $this->belongsTo(Transporteur::class, 'transporteur_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DocumentLine::class, 'doc_id');
    }

    public function reglements(): HasMany
    {
        return $this->hasMany(Reglement::class, 'doc_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'reference_id');
    }

    public function scopeByModule(Builder $query, string $module): Builder
    {
        $codes = array_keys(DocumentTypeRegistry::codesByModule($module));
        return $query->whereIn('type_document_code', $codes);
    }

    public function scopeSales(Builder $query): Builder
    {
        return $this->scopeByModule($query, DocumentTypeRegistry::MODULE_SALES);
    }

    public function scopePurchases(Builder $query): Builder
    {
        return $this->scopeByModule($query, DocumentTypeRegistry::MODULE_PURCHASE);
    }

    public function scopeStockDocs(Builder $query): Builder
    {
        return $this->scopeByModule($query, DocumentTypeRegistry::MODULE_STOCK);
    }

    public function getModuleAttribute(): string
    {
        return DocumentTypeRegistry::moduleFromCode((string) $this->type_document_code);
    }
}
