<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutboxEvent extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $table = 'outbox_events';

    protected $fillable = [
        'tenant_id',
        'event_name',
        'aggregate_type',
        'aggregate_id',
        'dedupe_key',
        'payload',
        'status',
        'available_at',
        'processed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'available_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
