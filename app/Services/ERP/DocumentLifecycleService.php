<?php

namespace App\Services\ERP;

use App\Models\Document;
use App\Services\StockMovementService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DocumentLifecycleService
{
    public function __construct(
        protected StockMovementService $stockMovementService,
        protected AccountingPostingService $accountingPostingService
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
        if (!in_array($document->lifecycle_status, ['validated', 'draft'], true)) {
            throw new RuntimeException('Only draft/validated documents can be posted.');
        }

        DB::transaction(function () use ($document): void {
            $document->loadMissing('lines');
            $this->stockMovementService->processDocumentMovement($document);
            $this->accountingPostingService->syncDocumentPosting($document);
            $document->update([
                'lifecycle_status' => 'posted',
                'posted_at' => now(),
            ]);
        });
    }

    public function cancel(Document $document): void
    {
        if ($document->lifecycle_status === 'cancelled') {
            return;
        }

        DB::transaction(function () use ($document): void {
            if ($document->lifecycle_status === 'posted') {
                $document->loadMissing('lines');
                $this->stockMovementService->deleteDocumentMovements($document);
                $this->accountingPostingService->clearDocumentPosting($document->id);
            }

            $document->update([
                'lifecycle_status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
        });
    }
}

