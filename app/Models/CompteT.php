<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompteT extends Model
{
    use HasFactory;

    protected $table = 'f_comptet';

    protected $fillable = [
        'ct_num',
        'code_tiers',
        'ct_intitule',
        'ct_type',
        'ct_ice',
        'ct_if',
        'ct_encours_max',
        'ct_delai_paiement',
        'ct_telephone',
        'ct_adresse',
    ];

    protected function casts(): array
    {
        return [
            'ct_encours_max' => 'decimal:2',
            'ct_delai_paiement' => 'integer',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'tier_id');
    }

    public function reglements(): HasMany
    {
        return $this->hasMany(Reglement::class, 'tier_id');
    }

    public function scopeClients(Builder $query): Builder
    {
        return $query->where('ct_type', 'client');
    }

    public function scopeSuppliers(Builder $query): Builder
    {
        return $query->where('ct_type', 'fournisseur');
    }
}
