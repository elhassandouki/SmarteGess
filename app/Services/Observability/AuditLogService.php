<?php

namespace App\Services\Observability;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public function log(string $eventType, string $entityType, int|string|null $entityId, array $payload = [], string $severity = 'info'): void
    {
        AuditLog::create([
            'actor_id' => Auth::id(),
            'event_type' => $eventType,
            'entity_type' => $entityType,
            'entity_id' => $entityId ? (string) $entityId : null,
            'severity' => $severity,
            'payload' => $payload,
            'trace_id' => request()?->header('X-Request-Id'),
        ]);
    }
}
