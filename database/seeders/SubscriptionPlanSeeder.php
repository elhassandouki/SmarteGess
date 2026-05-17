<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'code' => 'starter',
                'name' => 'Starter',
                'price_mad' => 199,
                'max_users' => 2,
                'max_documents_per_month' => 300,
                'max_storage_gb' => 2,
                'features' => ['sales', 'purchase', 'inventory_basic'],
            ],
            [
                'code' => 'growth',
                'name' => 'Growth',
                'price_mad' => 499,
                'max_users' => 8,
                'max_documents_per_month' => 3000,
                'max_storage_gb' => 20,
                'features' => ['sales', 'purchase', 'inventory', 'accounting', 'pdf_branding'],
            ],
            [
                'code' => 'pro',
                'name' => 'Pro',
                'price_mad' => 999,
                'max_users' => 25,
                'max_documents_per_month' => 20000,
                'max_storage_gb' => 100,
                'features' => ['all'],
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(['code' => $plan['code']], $plan + ['is_active' => true]);
        }
    }
}
