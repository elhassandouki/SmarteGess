<?php

namespace App\Modules\Sales\Domain\Events;

use App\Models\Document;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentCancelled
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Document $document
    ) {
    }
}
