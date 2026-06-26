<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\Voucher;
use Illuminate\Database\Seeder;

class KonaMangallayaVoucherSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('domain', 'cxfit')->firstOrFail();

        $vouchers = [
            ['name' => 'Kona Mangallaya 2026 - 2000',         'uuid' => '3174d71b-d867-4c9b-94f5-8d5b1f1d30ac', 'amount' => 2000.00],
            ['name' => 'Kona Mangallaya 2026 - 2000',         'uuid' => 'edf49e32-6fac-4d5a-b709-0ae8f339464d', 'amount' => 2000.00],
            ['name' => 'Kona Mangallaya 2026 - 2000',         'uuid' => '443706a1-3f26-40df-8b3d-890f72fdf341', 'amount' => 2000.00],
            ['name' => 'Kona Mangallaya 2026 - 2000',         'uuid' => '821e8286-5e5e-4fa6-bded-e1bd064d01d6', 'amount' => 2000.00],
            ['name' => 'Kona Mangallaya 2026 - 2000',         'uuid' => 'ec283873-3ec7-4a39-95d8-5272776ed9fe', 'amount' => 2000.00],
            ['name' => 'Kona Mangallaya 2026 - 2000 (Extra)', 'uuid' => 'd141f2a0-e586-4541-86c1-db234dffaf7a', 'amount' => 2000.00],
            ['name' => 'Kona Mangallaya 2026 - 2000 (Extra)', 'uuid' => 'ab243fc7-984f-4775-9247-eb2ac0a2e976', 'amount' => 2000.00],
            ['name' => 'Kona Mangallaya 2026 - 3000',         'uuid' => 'a74f9d2d-c99b-4d6c-bfd6-7132ff608ba0', 'amount' => 3000.00],
            ['name' => 'Kona Mangallaya 2026 - 3000',         'uuid' => 'e499e6da-9c82-4f5a-997e-5c9c1f8bc2c2', 'amount' => 3000.00],
            ['name' => 'Kona Mangallaya 2026 - 3000',         'uuid' => 'dc1bd6a8-e2ed-4e1a-aa54-87614d27ea35', 'amount' => 3000.00],
            ['name' => 'Kona Mangallaya 2026 - 10000',        'uuid' => '016fe78c-81ab-4227-a6e4-dbbc1501d150', 'amount' => 10000.00],
            ['name' => 'Kona Mangallaya 2026 - 15000',        'uuid' => '68dcf0ea-f7e8-4604-aa49-88d1aa12dd38', 'amount' => 15000.00],
        ];

        foreach ($vouchers as $data) {
            Voucher::firstOrCreate(
                ['uuid' => $data['uuid']],
                [
                    'name' => $data['name'],
                    'amount' => $data['amount'],
                    'status' => 'active',
                    'valid_from' => null,
                    'valid_until' => null,
                    'created_by' => null,
                ],
            );
        }
    }
}
