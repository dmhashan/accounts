<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('members', 'username')) {
            return;
        }

        $usernameIndexes = collect(Schema::getIndexes('members'))
            ->filter(fn (array $index): bool => in_array('username', $index['columns'] ?? [], true))
            ->values();

        Schema::table('members', function (Blueprint $table) use ($usernameIndexes) {
            foreach ($usernameIndexes as $index) {
                if ($index['unique'] ?? false) {
                    $table->dropUnique($index['name']);
                } else {
                    $table->dropIndex($index['name']);
                }
            }

            $table->dropColumn('username');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('members', 'username')) {
            return;
        }

        Schema::table('members', function (Blueprint $table) {
            $table->string('username')->nullable()->after('last_name');
            $table->unique(['tenant_id', 'username']);
        });
    }
};
