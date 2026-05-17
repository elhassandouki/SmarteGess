<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reglement extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $table = 'f_reglements';

    protected $fillable = [
        'tenant_id',
        'doc_id',
        'tier_id',
        'rg_date',
        'rg_libelle',
        'rg_montant',
        'rg_mode_reglement',
        'rg_reference',
        'rg_date_echeance',
        'rg_banque',
        'rg_valide',
    ];

    protected function casts(): array
    {
        return [
            'rg_date' => 'date',
            'rg_date_echeance' => 'date',
            'rg_montant' => 'decimal:2',
            'rg_mode_reglement' => 'integer',
            'rg_valide' => 'boolean',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'doc_id');
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(CompteT::class, 'tier_id');
    }
}
