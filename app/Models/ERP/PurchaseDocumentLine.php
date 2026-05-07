<?php

namespace App\Models\ERP;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseDocumentLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_document_id',
        'article_id',
        'quantity',
        'unit_price_ht',
        'discount_percent',
        'tva_percent',
        'line_total_ht',
        'line_total_ttc',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price_ht' => 'decimal:5',
            'discount_percent' => 'decimal:2',
            'tva_percent' => 'decimal:2',
            'line_total_ht' => 'decimal:2',
            'line_total_ttc' => 'decimal:2',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(PurchaseDocument::class, 'purchase_document_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
