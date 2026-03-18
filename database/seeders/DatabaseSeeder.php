<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Fokus: Guna data 'iseed' dari local SQLite supaya data 100% sama.
     * Turutan: Penting untuk elak error Foreign Key (FK).
     */
    public function run(): void
    {
        // 1. Matikan sekatan Foreign Key kejap supaya MariaDB tak 'bising'
        Schema::disableForeignKeyConstraints();

        $this->call([
            // --- ASAS (Roles & Permissions) ---
            RolesTableSeeder::class,
            PermissionsTableSeeder::class,

            // --- STRUKTUR (Organizations) ---
            OrganizationsTableSeeder::class,

            // --- PENGGUNA (Users) ---
            UsersTableSeeder::class,

            // --- HUBUNGAN (Pivot Tables Spatie) ---
            // Sangat penting untuk elak Error 403!
            ModelHasRolesTableSeeder::class,

            // --- DATA KONTEN (Events & Infaq) ---
            EventsTableSeeder::class,
            InfaqTableSeeder::class,
            UsrahGroupsTableSeeder::class,
            
            // Tambah lagi fail seeder iseed kau kat bawah ni kalau ada...
        ]);

        // 2. Hidupkan balik sekatan Foreign Key
        Schema::enableForeignKeyConstraints();
    }
}