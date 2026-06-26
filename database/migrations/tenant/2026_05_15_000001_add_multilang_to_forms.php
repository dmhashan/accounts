<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            // Language-keyed map: { "si": { "title": "...", "description": "...", "fields": { "field-id": { "label": "...", "placeholder": "...", "options": [] } } } }
            $table->json('translations')->nullable()->after('fields');
        });

        Schema::table('form_submissions', function (Blueprint $table) {
            // Language code of the language used when filling the form
            $table->string('language', 10)->nullable()->default('en')->after('responses');
        });
    }

    public function down(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            $table->dropColumn('translations');
        });

        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropColumn('language');
        });
    }
};
