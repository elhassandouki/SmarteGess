<?php

namespace App\Modules\Accounting\Application\Listeners;

use App\Modules\Sales\Domain\Events\DocumentPosted;
use App\Services\ERP\AccountingPostingService;

class PostAccountingOnDocumentPosted
{
    public function __construct(
        private readonly AccountingPostingService $accountingPostingService
    ) {
    }

    public function handle(DocumentPosted $event): void
    {
        $document = $event->document->fresh();

        if (!$document) {
            return;
        }

        $this->accountingPostingService->syncDocumentPosting($document);
    }
}
