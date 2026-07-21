<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::create('tenant_operation_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_subdomain')->index();
            $table->enum('operation', ['create', 'update', 'delete']);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->unsignedInteger('current_step')->default(0);
            $table->unsignedInteger('total_steps')->default(0);
            $table->json('steps');
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_operation_jobs');
    }
};
