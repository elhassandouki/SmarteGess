<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->tenant_id) {
            return redirect()->route('saas.onboarding.show');
        }

        $tenant = Tenant::find($user->tenant_id);
        if (!$tenant || !$tenant->is_active) {
            abort(403, 'Tenant suspended. Contact support.');
        }

        return $next($request);
    }
}
