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
            'name' => 'Company 1',
            'domain' => 'company-1',
        ]);

        Tenant::create([
            'name' => 'Company 2',
            'domain' => 'company-2',
        ]);

        Tenant::create([
            'name' => 'Company 3',
            'domain' => 'company-3',
        ]);
    }
}
