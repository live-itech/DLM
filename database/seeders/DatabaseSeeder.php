<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Role & permission
        $this->call(RolePermissionSeeder::class);

        // 1b. Master data + profil perusahaan
        $this->call(MasterDataSeeder::class);

        // 2. Akun Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@dimaslovemedika.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles('Admin');

        // 3. Akun Staff
        $staff = User::firstOrCreate(
            ['email' => 'staff@dimaslovemedika.com'],
            [
                'name' => 'Staff Operasional',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $staff->syncRoles('Staff');
    }
}
