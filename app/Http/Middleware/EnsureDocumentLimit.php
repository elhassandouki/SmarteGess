<?php

namespace App\Http\Middleware;

use App\Services\SaaS\PlanLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDocumentLimit
{
    public function __construct(
        private readonly PlanLimitService $planLimitService
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->tenant_id && !$this->planLimitService->canCreateDocument((int) $user->tenant_id)) {
            return redirect()->route('documents.index')->with('error', 'Limite mensuelle de documents atteinte pour votre plan.');
        }

        return $next($request);
    }
}
