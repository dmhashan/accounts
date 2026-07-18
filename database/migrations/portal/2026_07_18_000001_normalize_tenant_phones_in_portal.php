<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The database connection that should be used by the migration.
     *
     * @var string
     */
    protected $connection = 'central';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('tenants')) {
            $tenants = DB::connection('central')->table('tenants')->get();

            foreach ($tenants as $t) {
                if ($t->phone) {
                    $normalized = $this->normalizeToSriLankan($t->phone);

                    if ($normalized !== $t->phone) {
                        DB::connection('central')->table('tenants')
                            ->where('subdomain', $t->subdomain)
                            ->update(['phone' => $normalized]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // One-way migration: cannot reliably revert normalized numbers back to local format
    }

    /**
     * Normalize a phone number to standard +94XXXXXXXXX Sri Lankan format.
     */
    private function normalizeToSriLankan(?string $number): ?string
    {
        if ($number === null || trim($number) === '') {
            return $number;
        }

        $clean = preg_replace('/\D/', '', $number);

        if ($clean === '') {
            return $number;
        }

        if (str_starts_with($clean, '0')) {
            return '+94' . substr($clean, 1);
        }

        if (str_starts_with($clean, '94') && strlen($clean) === 11) {
            return '+' . $clean;
        }

        if (str_starts_with($clean, '7') && strlen($clean) === 9) {
            return '+94' . $clean;
        }

        if (strlen($clean) === 9) {
            return '+94' . $clean;
        }

        return '+' . $clean;
    }
};
