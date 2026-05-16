<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\CompteT;
use App\Models\Document;
use App\Models\Family;
use App\Policies\ArticlePolicy;
use App\Policies\CompteTPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\FamilyPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Article::class => ArticlePolicy::class,
        Family::class => FamilyPolicy::class,
        CompteT::class => CompteTPolicy::class,
        Document::class => DocumentPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}

