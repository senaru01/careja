<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Some positions are only for men or only for women. Null means both.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (['recruitment_positions', 'exam_positions'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('gender')->nullable()->after('quota');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['recruitment_positions', 'exam_positions'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('gender');
            });
        }
    }
};
