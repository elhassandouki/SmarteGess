<?php

namespace App\Services\Reliability;

use App\Models\OutboxEvent;

class OutboxService
{
    public function record(
        string $eventName,
        string $aggregateType,
        int|string $aggregateId,
        string $dedupeKey,
        array $payload
    ): OutboxEvent {
        return OutboxEvent::firstOrCreate(
            ['dedupe_key' => $dedupeKey],
            [
                'event_name' => $eventName,
                'aggregate_type' => $aggregateType,
                'aggregate_id' => (string) $aggregateId,
                'payload' => $payload,
                'status' => 'pending',
                'available_at' => now(),
            ]
        );
    }
}
