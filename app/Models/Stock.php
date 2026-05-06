<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stock extends Model
{
    use HasFactory;

    protected $table = 'f_stock';

    protected $fillable = [
        'article_id',
        'depot_id',
        'stock_reel',
        'stock_reserve',
    ];

    protected function casts(): array
    {
        return [
            'stock_reel' => 'decimal:3',
            'stock_reserve' => 'decimal:3',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class, 'depot_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'article_id', 'article_id')
            ->where('depot_id', $this->depot_id);
    }
}
