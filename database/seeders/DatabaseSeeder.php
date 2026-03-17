<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Order matters: Organizations must exist before Users reference them via
     * the current_organization_id FK, and Spatie roles/permissions before any
     * user is assigned a role.
     */
    public function run(): void
    {
        // 1. NGO tiers (PKPIM, ABIM, WADAH)
        $this->call(OrganizationSeeder::class);

        // 2. Spatie roles and permissions
        $this->call(RoleAndPermissionSeeder::class);

        // 3. Demo superadmin account
        $this->call(AdminUserSeeder::class);

        // 4. Demo events (one per NGO tier)
        $this->call(EventSeeder::class);
    }
}
