<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tenant::firstOrCreate(
            ['domain' => 'gym'],
            ['name' => 'CoreX Fitness', 'tenant_uuid' => (string) Str::uuid()],
        );

        Tenant::firstOrCreate(
            ['domain' => 'test'],
            ['name' => 'Test Tenant', 'tenant_uuid' => (string) Str::uuid()],
        );
    }
}
