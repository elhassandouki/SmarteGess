<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_for_protected_pages(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));
        $this->get(route('documents.index'))->assertRedirect(route('login'));
    }

    public function test_commercial_can_access_documents_but_not_accounting_payments(): void
    {
        $user = User::factory()->create(['role' => 'COMMERCIAL']);
        Permission::findOrCreate('documents.view', 'web');
        Permission::findOrCreate('reglements.view', 'web');
        $user->givePermissionTo('documents.view');

        $this->actingAs($user)
            ->get(route('documents.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('reglements.index'))
            ->assertForbidden();
    }

    public function test_accountant_can_access_payments_and_stock(): void
    {
        $user = User::factory()->create(['role' => 'COMPTABLE']);
        Permission::findOrCreate('reglements.view', 'web');
        Permission::findOrCreate('stocks.view', 'web');
        $user->givePermissionTo(['reglements.view', 'stocks.view']);

        $this->actingAs($user)
            ->get(route('reglements.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('stocks.index'))
            ->assertOk();
    }
}
