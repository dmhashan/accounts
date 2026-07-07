<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('reconciliation_configs');
        Schema::dropIfExists('reconciliation_entries');
        Schema::dropIfExists('reconciliation_sessions');

        Permission::whereIn('slug', ['reconciliation.manage', 'reconciliation.perform'])->delete();
    }

    public function down(): void
    {
        // No rollback since the feature is being removed.
    }
};
