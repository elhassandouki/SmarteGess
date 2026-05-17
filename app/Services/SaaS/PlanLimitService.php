<?php

namespace App\Services\SaaS;

use App\Models\Document;
use App\Models\TenantSubscription;
use App\Models\User;

class PlanLimitService
{
    public function canCreateDocument(int $tenantId): bool
    {
        $subscription = TenantSubscription::with('plan')
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->first();

        if (!$subscription || !$subscription->plan) {
            return true;
        }

        $limit = (int) $subscription->plan->max_documents_per_month;
        if ($limit <= 0) {
            return true;
        }

        $count = Document::where('tenant_id', $tenantId)
            ->whereBetween('do_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->count();

        return $count < $limit;
    }

    public function canInviteUser(int $tenantId): bool
    {
        $subscription = TenantSubscription::with('plan')
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->first();

        if (!$subscription || !$subscription->plan) {
            return true;
        }

        $limit = (int) $subscription->plan->max_users;
        if ($limit <= 0) {
            return true;
        }

        return User::where('tenant_id', $tenantId)->count() < $limit;
    }
}
