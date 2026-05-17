<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $table = 'stock_movements';

    protected $fillable = [
        'tenant_id',
        'article_id',
        'depot_id',
        'movement_type',
        'type',
        'quantity',
        'reference',
        'reference_type',
        'reference_id',
        'source_type',
        'source_id',
        'user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new \LogicException('Stock ledger entries are immutable and cannot be updated.');
        });

        static::deleting(function (): void {
            throw new \LogicException('Stock ledger entries are immutable and cannot be deleted.');
        });
    }
}
