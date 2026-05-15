<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('form_template_id');
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('submitted_by')->nullable(); // staff who submitted on behalf
            $table->json('responses');          // keyed by field id → value
            $table->string('pdf_path', 1000)->nullable();  // stored PDF in MediaStorage
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('form_template_id')->references('id')->on('form_templates')->onDelete('cascade');
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['tenant_id', 'member_id']);
            $table->index(['tenant_id', 'form_template_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
