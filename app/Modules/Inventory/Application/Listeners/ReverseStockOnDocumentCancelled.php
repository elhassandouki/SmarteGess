<?php

namespace App\Modules\Inventory\Application\Listeners;

use App\Modules\Sales\Domain\Events\DocumentCancelled;
use App\Services\StockMovementService;

class ReverseStockOnDocumentCancelled
{
    public function __construct(
        private readonly StockMovementService $stockMovementService
    ) {
    }

    public function handle(DocumentCancelled $event): void
    {
        $document = $event->document->fresh('lines');

        if (!$document) {
            return;
        }

        $this->stockMovementService->deleteDocumentMovements($document);
    }
}
