<?php

namespace App\Http\Controllers\SaaS;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Services\SaaS\TenantOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function __construct(
        private readonly TenantOnboardingService $onboardingService
    ) {
        $this->middleware('auth');
    }

    public function show(): View|RedirectResponse
    {
        $user = auth()->user();
        if ($user && $user->tenant_id) {
            return redirect()->route('home');
        }

        return view('saas.onboarding', [
            'plans' => SubscriptionPlan::where('is_active', true)->orderBy('price_mad')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_ice' => ['nullable', 'string', 'max:50'],
            'company_if' => ['nullable', 'string', 'max:50'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'invoice_prefix' => ['nullable', 'string', 'max:20'],
            'plan_code' => ['nullable', 'string', 'max:40'],
            'demo_mode' => ['nullable', 'boolean'],
        ]);

        $data['demo_mode'] = $request->boolean('demo_mode');

        $this->onboardingService->bootstrap($request->user(), $data);

        return redirect()->route('home')->with('success', 'Configuration terminee. Bienvenue dans votre ERP SaaS.');
    }
}
