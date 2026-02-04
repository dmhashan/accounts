<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tenant::firstOrCreate(
            ['domain' => 'gym'],
            ['name' => 'CoreX Fitness']
        );

        Tenant::firstOrCreate(
            ['domain' => 'test'],
            ['name' => 'Test Tenant']
        );
    }
}
