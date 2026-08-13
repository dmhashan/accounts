<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workout_program_assignments', function (Blueprint $table) {
            $table->string('type')->default('program')->after('member_id');
            $table->string('title')->nullable()->after('type');
            $table->foreignId('source_program_id')->nullable()->change();
            $table->foreignId('assigned_program_id')->nullable()->change();
            $table->string('file_path')->nullable()->after('effective_date');
            $table->string('file_name')->nullable()->after('file_path');
            $table->string('mime_type', 100)->nullable()->after('file_name');
            $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
            $table->longText('formatted_text')->nullable()->after('file_size');
            $table->text('notes')->nullable()->after('formatted_text');
        });
    }

    public function down(): void
    {
        Schema::table('workout_program_assignments', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'title',
                'file_path',
                'file_name',
                'mime_type',
                'file_size',
                'formatted_text',
                'notes',
            ]);
        });
    }
};
