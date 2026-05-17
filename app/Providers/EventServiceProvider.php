<?php

namespace App\Providers;

use App\Modules\Accounting\Application\Listeners\PostAccountingOnDocumentPosted;
use App\Modules\Accounting\Application\Listeners\ReverseAccountingOnDocumentCancelled;
use App\Modules\Inventory\Application\Listeners\ApplyStockOnDocumentPosted;
use App\Modules\Inventory\Application\Listeners\ReverseStockOnDocumentCancelled;
use App\Modules\Sales\Domain\Events\DocumentCancelled;
use App\Modules\Sales\Domain\Events\DocumentPosted;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        DocumentPosted::class => [
            ApplyStockOnDocumentPosted::class,
            PostAccountingOnDocumentPosted::class,
        ],
        DocumentCancelled::class => [
            ReverseStockOnDocumentCancelled::class,
            ReverseAccountingOnDocumentCancelled::class,
        ],
    ];
}
