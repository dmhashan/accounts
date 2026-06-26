<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_summary_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->date('report_date')->index();
            $table->unsignedBigInteger('prepared_by_user_id')->nullable()->index();
            $table->string('prepared_by_name');
            $table->string('signature_path')->nullable();
            $table->string('selfie_path')->nullable();
            $table->json('system_snapshot');
            $table->json('final_snapshot');
            $table->json('changes')->nullable();
            $table->json('totals')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'report_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_summary_reports');
    }
};
