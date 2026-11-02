<?php

use Carbon\Carbon;
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
        Schema::table('candidate_profiles', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('user_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('verified_at')->index();
        });

        // Payments verified before this change get one year from their verification date.
        DB::table('payments')
            ->where('status', 'verified')
            ->whereNotNull('verified_at')
            ->orderBy('id')
            ->each(fn (object $payment) => DB::table('payments')
                ->where('id', $payment->id)
                ->update(['expires_at' => Carbon::parse($payment->verified_at)->addYear()]));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropColumn('expires_at');
        });

        Schema::table('candidate_profiles', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};
