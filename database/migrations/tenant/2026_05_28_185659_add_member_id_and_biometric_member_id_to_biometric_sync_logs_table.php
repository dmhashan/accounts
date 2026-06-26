<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure `biometric_sync_logs` has the correct schema:
     *
     *   member_id          — nullable FK → members.id  (DB member record)
     *   biometric_member_id — nullable VARCHAR           (device employee number)
     *
     * The legacy table had `biometric_member_id` as a bigint FK to members.
     * This migration migrates that data into `member_id` and converts the
     * `biometric_member_id` column to a nullable string.
     */
    public function up(): void
    {
        // 1. Ensure member_id column exists (plain bigint first, FK added later)
        if (!Schema::hasColumn('biometric_sync_logs', 'member_id')) {
            Schema::table('biometric_sync_logs', function (Blueprint $table) {
                $table->unsignedBigInteger('member_id')->nullable()->after('tenant_id');
            });
        }

        // Non-MySQL drivers (e.g. SQLite for tests) don't support information_schema
        // queries or ENUM/FK manipulation; just ensure the columns exist and stop.
        if (DB::getDriverName() !== 'mysql') {
            if (!Schema::hasColumn('biometric_sync_logs', 'biometric_member_id')) {
                Schema::table('biometric_sync_logs', function (Blueprint $table) {
                    $table->string('biometric_member_id')->nullable()->after('member_id');
                });
            }

            return;
        }

        // 2. If biometric_member_id exists as a numeric/FK column, migrate its
        //    values into member_id, then convert it to a varchar device ID field.
        if (Schema::hasColumn('biometric_sync_logs', 'biometric_member_id')) {
            // Determine the current column type
            $colType = DB::selectOne(
                "SELECT DATA_TYPE FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME   = 'biometric_sync_logs'
                   AND COLUMN_NAME  = 'biometric_member_id'",
            )->DATA_TYPE ?? '';

            if (in_array(strtolower($colType), ['bigint', 'int', 'smallint', 'tinyint', 'mediumint'])) {
                // Migrate existing FK values (members.id) → member_id
                DB::statement('UPDATE biometric_sync_logs SET member_id = biometric_member_id WHERE member_id IS NULL AND biometric_member_id IS NOT NULL');

                // Drop FK constraint on biometric_member_id if it exists
                $fkName = DB::selectOne(
                    "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                     WHERE TABLE_SCHEMA     = DATABASE()
                       AND TABLE_NAME       = 'biometric_sync_logs'
                       AND COLUMN_NAME      = 'biometric_member_id'
                       AND REFERENCED_TABLE_NAME IS NOT NULL",
                )->CONSTRAINT_NAME ?? null;

                if ($fkName) {
                    Schema::table('biometric_sync_logs', function (Blueprint $table) use ($fkName) {
                        $table->dropForeign($fkName);
                    });
                }

                // Change column type to varchar
                Schema::table('biometric_sync_logs', function (Blueprint $table) {
                    $table->string('biometric_member_id')->nullable()->change();
                });
            }
        } else {
            // Column doesn't exist at all — add as varchar
            Schema::table('biometric_sync_logs', function (Blueprint $table) {
                $table->string('biometric_member_id')->nullable()->after('member_id');
            });
        }

        // 3. Add FK constraint on member_id if not already present
        $memberFk = DB::selectOne(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA          = DATABASE()
               AND TABLE_NAME            = 'biometric_sync_logs'
               AND COLUMN_NAME           = 'member_id'
               AND REFERENCED_TABLE_NAME = 'members'",
        )->CONSTRAINT_NAME ?? null;

        if (!$memberFk) {
            // Drop any stale index that would conflict with the FK constraint name
            $staleIndex = DB::selectOne(
                "SELECT INDEX_NAME FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME   = 'biometric_sync_logs'
                   AND INDEX_NAME   = 'biometric_sync_logs_member_id_foreign'
                   AND COLUMN_NAME != 'member_id'",
            )->INDEX_NAME ?? null;

            if ($staleIndex) {
                DB::statement('ALTER TABLE `biometric_sync_logs` DROP INDEX `biometric_sync_logs_member_id_foreign`');
            }

            Schema::table('biometric_sync_logs', function (Blueprint $table) {
                $table->foreign('member_id')->references('id')->on('members')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            if (Schema::hasColumn('biometric_sync_logs', 'member_id')) {
                Schema::table('biometric_sync_logs', function (Blueprint $table) {
                    $table->dropColumn('member_id');
                });
            }

            return;
        }

        // Reverse: drop member_id FK and column; restore biometric_member_id as bigint FK
        $memberFk = DB::selectOne(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA          = DATABASE()
               AND TABLE_NAME            = 'biometric_sync_logs'
               AND COLUMN_NAME           = 'member_id'
               AND REFERENCED_TABLE_NAME = 'members'",
        )->CONSTRAINT_NAME ?? null;

        if ($memberFk) {
            Schema::table('biometric_sync_logs', function (Blueprint $table) use ($memberFk) {
                $table->dropForeign($memberFk);
            });
        }

        if (Schema::hasColumn('biometric_sync_logs', 'member_id')) {
            Schema::table('biometric_sync_logs', function (Blueprint $table) {
                $table->dropColumn('member_id');
            });
        }
    }
};
