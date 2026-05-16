<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view-erp',
            'families.view', 'families.create', 'families.update', 'families.delete',
            'articles.view', 'articles.create', 'articles.update', 'articles.delete',
            'tiers.view', 'tiers.create', 'tiers.update', 'tiers.delete',
            'transporteurs.view', 'transporteurs.create', 'transporteurs.update', 'transporteurs.delete',
            'depots.view', 'depots.create', 'depots.update', 'depots.delete',
            'documents.view', 'documents.create', 'documents.update', 'documents.delete', 'documents.duplicate', 'documents.status',
            'stocks.view', 'stocks.adjust',
            'reglements.view', 'reglements.create', 'reglements.delete',
            'access.roles.view', 'access.roles.create', 'access.roles.update', 'access.roles.delete',
            'access.permissions.view', 'access.permissions.create', 'access.permissions.update', 'access.permissions.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $commercialRole = Role::firstOrCreate([
            'name' => 'commercial',
            'guard_name' => 'web',
        ]);

        $comptableRole = Role::firstOrCreate([
            'name' => 'comptable',
            'guard_name' => 'web',
        ]);

        $magasinierRole = Role::firstOrCreate([
            'name' => 'magasinier',
            'guard_name' => 'web',
        ]);

        $userRole = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'web',
        ]);

        $adminRole->syncPermissions(Permission::where('guard_name', 'web')->get());
        $commercialRole->syncPermissions([
            'view-erp',
            'families.view', 'families.create', 'families.update',
            'articles.view', 'articles.create', 'articles.update',
            'tiers.view', 'tiers.create', 'tiers.update',
            'transporteurs.view', 'transporteurs.create', 'transporteurs.update',
            'documents.view', 'documents.create', 'documents.update', 'documents.duplicate', 'documents.status',
        ]);
        $comptableRole->syncPermissions([
            'view-erp',
            'families.view',
            'articles.view',
            'tiers.view',
            'transporteurs.view',
            'depots.view', 'depots.create', 'depots.update',
            'documents.view', 'documents.status',
            'stocks.view',
            'reglements.view', 'reglements.create', 'reglements.delete',
        ]);
        $magasinierRole->syncPermissions([
            'view-erp',
            'articles.view',
            'depots.view',
            'stocks.view', 'stocks.adjust',
        ]);
        $userRole->syncPermissions(['view-erp']);

        $admin = User::firstOrCreate([
            'email' => 'admin@test.com',
        ], [
            'name' => 'Admin',
            'password' => '123456',
            'role' => 'ADMIN',
        ]);

        $admin->forceFill([
            'name' => 'Admin',
            'password' => '123456',
            'role' => 'ADMIN',
        ])->save();

        $admin->syncRoles([$adminRole]);

        if (DB::table('f_taxes')->where('code_taxe', 'TVA0')->doesntExist()) {
            DB::table('f_taxes')->insert([
                ['code_taxe' => 'TVA0', 'libelle' => 'TVA 0%', 'taux' => 0, 'created_at' => now(), 'updated_at' => now()],
                ['code_taxe' => 'TVA7', 'libelle' => 'TVA 7%', 'taux' => 7, 'created_at' => now(), 'updated_at' => now()],
                ['code_taxe' => 'TVA10', 'libelle' => 'TVA 10%', 'taux' => 10, 'created_at' => now(), 'updated_at' => now()],
                ['code_taxe' => 'TVA14', 'libelle' => 'TVA 14%', 'taux' => 14, 'created_at' => now(), 'updated_at' => now()],
                ['code_taxe' => 'TVA20', 'libelle' => 'TVA 20%', 'taux' => 20, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        DB::table('f_depots')->updateOrInsert(
            ['code_depot' => 'DEP01'],
            ['intitule' => 'DEPOT PRINCIPAL', 'updated_at' => now(), 'created_at' => now()]
        );
    }
}
