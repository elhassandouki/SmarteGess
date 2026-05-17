<?php

namespace App\Services\ERP;

use App\Models\Document;
use App\Modules\Sales\Domain\Events\DocumentCancelled;
use App\Modules\Sales\Domain\Events\DocumentPosted;
use App\Services\Observability\AuditLogService;
use App\Services\Reliability\OutboxService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DocumentLifecycleService
{
    public function __construct(
        private readonly OutboxService $outboxService,
        private readonly AuditLogService $auditLogService
    ) {
    }

    public function validate(Document $document): void
    {
        if ($document->lifecycle_status !== 'draft') {
            throw new RuntimeException('Only draft documents can be validated.');
        }

        $document->update(['lifecycle_status' => 'validated']);
    }

    public function post(Document $document): void
    {
        DB::transaction(function () use ($document): void {
            $locked = Document::query()->whereKey($document->id)->lockForUpdate()->firstOrFail();

            if ($locked->lifecycle_status !== 'validated') {
                throw new RuntimeException('Only validated documents can be posted.');
            }

            $locked->update([
                'lifecycle_status' => 'posted',
                'posted_at' => now(),
            ]);

            $dedupeKey = 'document.posted:'.$locked->id.':'.$locked->updated_at?->timestamp;
            $this->outboxService->record(
                eventName: 'document.posted',
                aggregateType: 'document',
                aggregateId: $locked->id,
                dedupeKey: $dedupeKey,
                payload: ['document_id' => $locked->id]
            );

            $this->auditLogService->log('document.posted', 'document', $locked->id, [
                'status' => $locked->lifecycle_status,
                'piece' => $locked->do_piece,
            ]);

            DB::afterCommit(function () use ($locked): void {
                event(new DocumentPosted($locked));
            });
        });
    }

    public function cancel(Document $document): void
    {
        if ($document->lifecycle_status === 'cancelled') {
            return;
        }

        DB::transaction(function () use ($document): void {
            $locked = Document::query()->whereKey($document->id)->lockForUpdate()->firstOrFail();

            if ($locked->lifecycle_status === 'posted') {
                $dedupeKey = 'document.cancelled:'.$locked->id.':'.now()->timestamp;
                $this->outboxService->record(
                    eventName: 'document.cancelled',
                    aggregateType: 'document',
                    aggregateId: $locked->id,
                    dedupeKey: $dedupeKey,
                    payload: ['document_id' => $locked->id]
                );

                DB::afterCommit(function () use ($locked): void {
                    event(new DocumentCancelled($locked));
                });
            }

            $locked->update([
                'lifecycle_status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            $this->auditLogService->log('document.cancelled', 'document', $locked->id, [
                'status' => $locked->lifecycle_status,
                'piece' => $locked->do_piece,
            ]);
        });
    }
}
