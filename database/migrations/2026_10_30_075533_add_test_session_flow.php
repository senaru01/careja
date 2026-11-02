<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Test sessions: an exam is announced for a company (position, qualifications,
 * quota, schedule), published after the LPK pays, and joined by job seekers
 * who pay per session. Interviews pick from the session ranking and end with
 * a medical checkup.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('organization_id')->constrained('organizations')->nullOnDelete();
            $table->string('position')->nullable()->after('title');
            $table->text('qualifications')->nullable()->after('position');
            $table->unsignedSmallInteger('quota')->nullable()->after('qualifications');
            $table->unsignedSmallInteger('min_participants')->nullable()->after('quota');
            $table->dateTime('starts_at')->nullable()->after('passing_score');
            $table->dateTime('ends_at')->nullable()->after('starts_at');
            $table->timestamp('published_at')->nullable()->after('ends_at');
        });

        Schema::create('exam_reschedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rescheduled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('previous_starts_at');
            $table->dateTime('previous_ends_at');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->text('reason');
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('purpose')->default('membership')->after('user_id')->index();
            $table->foreignId('exam_id')->nullable()->after('purpose')->constrained()->nullOnDelete();
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->timestamp('qualification_confirmed_at')->nullable()->after('assigned_by');
            $table->timestamp('registered_at')->nullable()->after('qualification_confirmed_at');
            $table->timestamp('withdrawn_at')->nullable()->after('registered_at');
        });

        Schema::create('exam_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_attempt_id')->nullable()->constrained('exam_attempts')->nullOnDelete();
            $table->foreignId('used_attempt_id')->nullable()->constrained('exam_attempts')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::table('interviews', function (Blueprint $table) {
            $table->foreignId('exam_id')->nullable()->after('organization_id')->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('exam_rank')->nullable()->after('exam_id');
            $table->text('skip_reason')->nullable()->after('exam_rank');
            $table->string('medical_status')->nullable()->after('evaluated_at');
            $table->text('medical_notes')->nullable()->after('medical_status');
            $table->foreignId('medical_checked_by')->nullable()->after('medical_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('medical_checked_at')->nullable()->after('medical_checked_by');
        });

        // Exams and attempts made with the old flow (direct assignment) stay usable.
        DB::table('exams')->whereNull('published_at')->update([
            'published_at' => DB::raw('created_at'),
            'starts_at' => DB::raw('created_at'),
            'ends_at' => now()->addMonth(),
        ]);
        DB::table('exam_attempts')->whereNull('registered_at')->update(['registered_at' => DB::raw('created_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exam_id');
            $table->dropConstrainedForeignId('medical_checked_by');
            $table->dropColumn(['exam_rank', 'skip_reason', 'medical_status', 'medical_notes', 'medical_checked_at']);
        });

        Schema::dropIfExists('exam_tokens');

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn(['qualification_confirmed_at', 'registered_at', 'withdrawn_at']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exam_id');
            $table->dropColumn('purpose');
        });

        Schema::dropIfExists('exam_reschedules');

        Schema::table('exams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn(['position', 'qualifications', 'quota', 'min_participants', 'starts_at', 'ends_at', 'published_at']);
        });
    }
};
