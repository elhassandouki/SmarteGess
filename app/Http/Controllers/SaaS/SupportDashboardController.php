<?php

namespace App\Http\Controllers\SaaS;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\OutboxEvent;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SupportDashboardController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Tenant::class);

        return view('support.dashboard', [
            'tenants' => Tenant::orderByDesc('id')->limit(20)->get(),
            'stats' => [
                'tenants_total' => Tenant::count(),
                'tenants_active' => Tenant::where('is_active', true)->count(),
                'outbox_pending' => OutboxEvent::where('status', 'pending')->count(),
                'outbox_failed' => OutboxEvent::where('status', 'failed')->count(),
                'failed_jobs' => \DB::table('failed_jobs')->count(),
                'audit_last_24h' => AuditLog::where('created_at', '>=', now()->subDay())->count(),
            ],
            'subscriptions' => TenantSubscription::with('plan')->latest('id')->limit(20)->get(),
        ]);
    }

    public function toggleTenant(Tenant $tenant): RedirectResponse
    {
        $this->authorize('update', $tenant);

        $tenant->update(['is_active' => !$tenant->is_active]);

        return back()->with('success', 'Statut tenant mis a jour.');
    }
}
