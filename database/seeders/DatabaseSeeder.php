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
            'view dashboard',
            'manage users',
            'manage families',
            'manage articles',
            'manage transporteurs',
            'manage documents',
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

        $userRole = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'web',
        ]);

        $adminRole->syncPermissions(Permission::where('guard_name', 'web')->get());
        $userRole->syncPermissions([
            'view dashboard',
            'manage articles',
            'manage documents',
        ]);

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
