<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('worker')->after('password')->index();
            $table->foreignId('organization_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('organization_id');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->string('two_factor_code')->nullable()->after('approved_by');
            $table->timestamp('two_factor_expires_at')->nullable()->after('two_factor_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn(['role', 'approved_at', 'two_factor_code', 'two_factor_expires_at']);
        });
    }
};
