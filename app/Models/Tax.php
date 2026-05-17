<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    use HasFactory;

    protected $table = 'f_taxes';

    protected $fillable = [
        'code_taxe',
        'libelle',
        'taux',
    ];

    protected function casts(): array
    {
        return [
            'taux' => 'decimal:2',
        ];
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->libelle} ({$this->taux}%)";
    }
}
