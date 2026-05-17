<?php

namespace App\Modules\Accounting\Application\Listeners;

use App\Modules\Sales\Domain\Events\DocumentCancelled;
use App\Services\ERP\AccountingPostingService;

class ReverseAccountingOnDocumentCancelled
{
    public function __construct(
        private readonly AccountingPostingService $accountingPostingService
    ) {
    }

    public function handle(DocumentCancelled $event): void
    {
        $document = $event->document;
        $this->accountingPostingService->clearDocumentPosting($document->id);
    }
}
