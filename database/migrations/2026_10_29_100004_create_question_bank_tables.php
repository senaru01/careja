<?php

use App\Models\QuestionCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('question_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'name']);
        });

        Schema::create('bank_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_category_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->timestamps();
        });

        Schema::create('bank_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_question_id')->constrained()->cascadeOnDelete();
            $table->string('text');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });

        // Exam questions remember where they were copied from, for the
        // per-category score breakdown and to avoid picking a question twice.
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->foreignId('question_category_id')->nullable()->after('exam_id')->constrained()->nullOnDelete();
            $table->foreignId('bank_question_id')->nullable()->after('question_category_id')->constrained()->nullOnDelete();
        });

        // Give existing LPKs the default categories.
        $now = now();
        DB::table('organizations')->where('type', 'lpk')->pluck('id')->each(function (int $organizationId) use ($now) {
            foreach (QuestionCategory::DEFAULTS as $name => $description) {
                DB::table('question_categories')->insert([
                    'organization_id' => $organizationId,
                    'name' => $name,
                    'description' => $description,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_question_id');
            $table->dropConstrainedForeignId('question_category_id');
        });

        Schema::dropIfExists('bank_question_options');
        Schema::dropIfExists('bank_questions');
        Schema::dropIfExists('question_categories');
    }
};
