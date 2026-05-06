<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transporteur extends Model
{
    use HasFactory;

    protected $table = 'f_transporteurs';

    protected $fillable = [
        'tr_nom',
        'tr_matricule',
        'tr_chauffeur',
        'tr_telephone',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'transporteur_id');
    }
}
