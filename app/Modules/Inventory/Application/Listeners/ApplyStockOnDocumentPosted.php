<?php

namespace App\Modules\Inventory\Application\Listeners;

use App\Modules\Sales\Domain\Events\DocumentPosted;
use App\Services\StockMovementService;

class ApplyStockOnDocumentPosted
{
    public function __construct(
        private readonly StockMovementService $stockMovementService
    ) {
    }

    public function handle(DocumentPosted $event): void
    {
        $document = $event->document->fresh('lines');

        if (!$document) {
            return;
        }

        $this->stockMovementService->processDocumentMovement($document);
    }
}
