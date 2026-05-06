<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Depot extends Model
{
    use HasFactory;

    protected $table = 'f_depots';

    protected $fillable = [
        'code_depot',
        'intitule',
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'depot_id');
    }
}
