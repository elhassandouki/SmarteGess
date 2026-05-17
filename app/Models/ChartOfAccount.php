<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    use HasFactory;

    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'account_code',
        'account_label',
        'account_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'account_code', 'account_code');
    }

    public function getTypeDisplayAttribute(): string
    {
        return match ($this->account_type) {
            'asset' => 'Actif',
            'liability' => 'Passif',
            'equity' => 'Capitaux propres',
            'revenue' => 'Produit',
            'expense' => 'Charge',
            default => $this->account_type,
        };
    }
}
