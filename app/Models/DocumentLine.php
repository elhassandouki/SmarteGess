<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentLine extends Model
{
    use HasFactory;

    protected $table = 'f_docligne';

    protected $fillable = [
        'doc_id',
        'article_id',
        'dl_qte',
        'dl_prix_unitaire_ht',
        'dl_prix_revient',
        'dl_remise_percent',
        'dl_montant_ht',
        'dl_montant_ttc',
    ];

    protected function casts(): array
    {
        return [
            'dl_qte' => 'decimal:3',
            'dl_prix_unitaire_ht' => 'decimal:5',
            'dl_prix_revient' => 'decimal:5',
            'dl_remise_percent' => 'decimal:2',
            'dl_montant_ht' => 'decimal:2',
            'dl_montant_ttc' => 'decimal:2',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'doc_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }
}
