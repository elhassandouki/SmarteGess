<?php

namespace App\Services\SaaS;

use App\Models\CompanySetting;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantOnboardingService
{
    public function bootstrap(User $user, array $data): Tenant
    {
        return DB::transaction(function () use ($user, $data): Tenant {
            $slug = Str::slug($data['company_name']);
            $base = $slug;
            $i = 2;
            while (Tenant::where('slug', $slug)->exists()) {
                $slug = $base.'-'.$i;
                $i++;
            }

            $tenant = Tenant::create([
                'name' => $data['company_name'],
                'slug' => $slug,
                'is_active' => true,
            ]);

            $user->update(['tenant_id' => $tenant->id]);

            CompanySetting::create([
                'tenant_id' => $tenant->id,
                'company_name' => $data['company_name'],
                'company_ice' => $data['company_ice'] ?? null,
                'company_if' => $data['company_if'] ?? null,
                'company_phone' => $data['company_phone'] ?? null,
                'company_email' => $data['company_email'] ?? $user->email,
                'company_address' => $data['company_address'] ?? null,
                'invoice_prefix' => $data['invoice_prefix'] ?? 'FAC',
            ]);

            $plan = SubscriptionPlan::where('code', $data['plan_code'] ?? 'starter')->first()
                ?? SubscriptionPlan::first();

            if ($plan) {
                TenantSubscription::create([
                    'tenant_id' => $tenant->id,
                    'subscription_plan_id' => $plan->id,
                    'status' => 'trial',
                    'starts_at' => now(),
                    'trial_ends_at' => now()->addDays(14),
                ]);
            }

            if (!empty($data['demo_mode'])) {
                app(DemoDataService::class)->seedForTenant($tenant->id);
            }

            return $tenant;
        });
    }
}
