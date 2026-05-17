<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $table = 'audit_logs';

    protected $fillable = [
        'tenant_id',
        'actor_id',
        'event_type',
        'entity_type',
        'entity_id',
        'severity',
        'payload',
        'trace_id',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
