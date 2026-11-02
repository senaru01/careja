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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('method')->default('manual')->after('amount');
            $table->string('proof_path')->nullable()->change();
            $table->string('gateway_order_id')->nullable()->unique()->after('proof_path');
            $table->string('gateway_transaction_id')->nullable()->after('gateway_order_id');
            $table->string('gateway_status')->nullable()->after('gateway_transaction_id');
            $table->text('qr_url')->nullable()->after('gateway_status');
            $table->text('qr_string')->nullable()->after('qr_url');
            $table->timestamp('payment_due_at')->nullable()->after('qr_string');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['gateway_order_id']);
            $table->dropColumn(['method', 'gateway_order_id', 'gateway_transaction_id', 'gateway_status', 'qr_url', 'qr_string', 'payment_due_at']);
        });
    }
};
