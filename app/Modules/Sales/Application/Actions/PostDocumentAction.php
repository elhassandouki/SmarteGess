<?php

namespace App\Modules\Sales\Application\Actions;

use App\Models\Document;
use App\Services\ERP\DocumentLifecycleService;

class PostDocumentAction
{
    public function __construct(
        private readonly DocumentLifecycleService $lifecycleService
    ) {
    }

    public function execute(Document $document): void
    {
        $this->lifecycleService->post($document);
    }
}
