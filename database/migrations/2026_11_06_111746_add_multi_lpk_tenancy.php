<?php

use App\Enums\OrganizationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Several LPKs, each with its own members on its own subdomain
 * (oki.careja.id), its own fees and payment settings. Companies (also on a
 * subdomain) are connected to one or more LPKs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('subdomain', 63)->nullable()->unique()->after('name');

            // Fees per LPK; null falls back to the default in config/careja.php.
            $table->unsignedInteger('membership_fee')->nullable()->after('description');
            $table->unsignedInteger('exam_session_fee')->nullable()->after('membership_fee');
            $table->unsignedInteger('exam_registration_fee')->nullable()->after('exam_session_fee');
            $table->unsignedInteger('interview_fee')->nullable()->after('exam_registration_fee');
            $table->unsignedInteger('medical_checkup_fee')->nullable()->after('interview_fee');

            $table->string('membership_collected_by')->default('careja')->after('medical_checkup_fee');
            $table->decimal('careja_share_percent', 5, 2)->default(0)->after('membership_collected_by');
            $table->string('bank_name')->nullable()->after('careja_share_percent');
            $table->string('bank_account_number', 50)->nullable()->after('bank_name');
            $table->string('bank_account_holder')->nullable()->after('bank_account_number');
        });

        Schema::create('company_lpk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('lpk_id')->constrained('organizations')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'lpk_id']);
        });

        Schema::create('recruitment_request_lpk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lpk_id')->constrained('organizations')->cascadeOnDelete();

            $table->unique(['recruitment_request_id', 'lpk_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('lpk_id')->nullable()->after('user_id')->constrained('organizations')->nullOnDelete();
            $table->string('collected_by')->default('careja')->after('method')->index();
        });

        // Keep today's behaviour for existing data: every company works with
        // every LPK and every request went to all of them.
        $lpkIds = DB::table('organizations')->where('type', OrganizationType::Lpk->value)->pluck('id');
        $companyIds = DB::table('organizations')->where('type', OrganizationType::Company->value)->pluck('id');

        DB::table('company_lpk')->insert($companyIds->crossJoin($lpkIds)->map(fn (array $pair) => [
            'company_id' => $pair[0],
            'lpk_id' => $pair[1],
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());

        DB::table('recruitment_request_lpk')->insert(
            DB::table('recruitment_requests')->pluck('id')->crossJoin($lpkIds)
                ->map(fn (array $pair) => ['recruitment_request_id' => $pair[0], 'lpk_id' => $pair[1]])->all()
        );

        // Payments tied to a session belong to the LPK running it.
        DB::table('payments')->whereNotNull('exam_id')->update([
            'lpk_id' => DB::raw('(select organization_id from exams where exams.id = payments.exam_id)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lpk_id');
            $table->dropColumn('collected_by');
        });

        Schema::dropIfExists('recruitment_request_lpk');
        Schema::dropIfExists('company_lpk');

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropUnique(['subdomain']);
            $table->dropColumn([
                'subdomain', 'membership_fee', 'exam_session_fee', 'exam_registration_fee', 'interview_fee',
                'medical_checkup_fee', 'membership_collected_by', 'careja_share_percent',
                'bank_name', 'bank_account_number', 'bank_account_holder',
            ]);
        });
    }
};
