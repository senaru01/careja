<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Medical checkup payments belong to one interview; demo payments (for the
 * Midtrans review) are real QRIS charges that never change anything else.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('interview_id')->nullable()->after('exam_id')->constrained()->nullOnDelete();
            $table->boolean('is_demo')->default(false)->after('interview_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('interview_id');
            $table->dropColumn('is_demo');
        });
    }
};
