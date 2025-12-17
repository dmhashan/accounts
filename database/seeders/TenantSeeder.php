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
        Tenant::create([
            'name' => 'CoreX Fitness',
            'domain' => 'gym',
        ]);

        Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test',
        ]);
    }
}
