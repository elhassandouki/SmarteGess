<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Family extends Model
{
    use HasFactory;

    protected $table = 'f_familles';

    protected $fillable = [
        'fa_code',
        'fa_intitule',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'family_id');
    }
}
