<?php

namespace App\Models\ERP;

use App\Models\CompteT;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'document_type',
        'status',
        'document_date',
        'customer_id',
        'source_document_id',
        'subtotal_ht',
        'total_tva',
        'total_ttc',
        'paid_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'subtotal_ht' => 'decimal:2',
            'total_tva' => 'decimal:2',
            'total_ttc' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CompteT::class, 'customer_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesDocumentLine::class);
    }
}
