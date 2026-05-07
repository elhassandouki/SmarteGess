<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'f_articles';

    protected $fillable = [
        'ar_ref',
        'code_article',
        'ar_design',
        'ar_code_barre',
        'family_id',
        'ar_prix_achat',
        'ar_prix_vente',
        'ar_prix_revient',
        'ar_tva',
        'ar_stock_min',
        'ar_stock_actuel',
        'ar_suivi_stock',
        'ar_unite',
    ];

    protected function casts(): array
    {
        return [
            'ar_prix_achat' => 'decimal:5',
            'ar_prix_vente' => 'decimal:5',
            'ar_prix_revient' => 'decimal:5',
            'ar_tva' => 'decimal:2',
            'ar_stock_min' => 'decimal:3',
            'ar_stock_actuel' => 'decimal:3',
            'ar_suivi_stock' => 'boolean',
        ];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'family_id');
    }

    public function documentLines(): HasMany
    {
        return $this->hasMany(DocumentLine::class, 'article_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'article_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'article_id');
    }
}
