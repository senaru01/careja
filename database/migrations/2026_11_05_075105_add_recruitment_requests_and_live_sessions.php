<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Recruitment requests: a company lists the positions it needs (quota,
 * minimum education, age range, experience) and an LPK turns the request
 * into a test session. Sessions get the same position table, participants
 * apply for one position, and the LPK starts the test manually because
 * everyone takes it together in one room.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recruitment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('needed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('recruitment_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_request_id')->constrained()->cascadeOnDelete();
            $this->positionColumns($table);
        });

        Schema::create('exam_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $this->positionColumns($table);
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->foreignId('recruitment_request_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->timestamp('started_at')->nullable()->after('published_at');
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->foreignId('exam_position_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        Schema::table('interviews', function (Blueprint $table) {
            $table->foreignId('exam_position_id')->nullable()->after('exam_id')->constrained()->nullOnDelete();
        });

        // Sessions published before the manual start existed keep running on their schedule.
        DB::table('exams')->whereNotNull('published_at')->update(['started_at' => DB::raw('starts_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exam_position_id');
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exam_position_id');
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recruitment_request_id');
            $table->dropColumn('started_at');
        });

        Schema::dropIfExists('exam_positions');
        Schema::dropIfExists('recruitment_positions');
        Schema::dropIfExists('recruitment_requests');
    }

    /**
     * One row of a position table: what is needed and who may apply.
     */
    private function positionColumns(Blueprint $table): void
    {
        $table->string('position');
        $table->unsignedSmallInteger('quota');
        $table->string('min_education')->nullable();
        $table->unsignedTinyInteger('min_age')->nullable();
        $table->unsignedTinyInteger('max_age')->nullable();
        $table->unsignedTinyInteger('min_experience_years')->default(0);
        $table->string('notes')->nullable();
        $table->timestamps();
    }
};
