<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    use HasFactory;

    protected $table = 'journal_entries';

    protected $fillable = [
        'entry_date',
        'journal_code',
        'reference_type',
        'reference_id',
        'reference_number',
        'label',
        'status',
        'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function getDebitTotalAttribute(): float
    {
        return (float) $this->lines->sum('debit');
    }

    public function getCreditTotalAttribute(): float
    {
        return (float) $this->lines->sum('credit');
    }
}
