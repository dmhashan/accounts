<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('name')->default('');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        // Try to backfill metadata from existing isolated databases
        try {
            $tenants = DB::connection('central')->table('tenants')->get();

            foreach ($tenants as $tenant) {
                $db = $tenant->database_name;

                if ($db && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $db)) {
                    // Check if schema information contains the database and table
                    $tableExists = DB::connection('central')->selectOne(
                        "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'tenants'",
                        [$db],
                    );

                    if ($tableExists) {
                        $details = DB::connection('central')->selectOne(
                            "SELECT name, email, phone, created_at, updated_at FROM `{$db}`.tenants WHERE tenant_uuid = ? LIMIT 1",
                            [$db],
                        );

                        if ($details) {
                            DB::connection('central')->table('tenants')->where('subdomain', $tenant->subdomain)->update([
                                'name' => $details->name ?? '',
                                'email' => $details->email,
                                'phone' => $details->phone,
                                'created_at' => $details->created_at,
                                'updated_at' => $details->updated_at,
                            ]);
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            // Silently catch and log for safety, migration must succeed even if db sync fails
            Log::warning('Central tenants metadata migration backfill error: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['name', 'email', 'phone', 'created_at', 'updated_at']);
        });
    }
};
