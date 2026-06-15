<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            // L1: notified to Supervisor / Chief (T+15 min)
            $table->timestamp('notified_l1_at')->nullable()->after('updated_at');
            // L2: escalated to Manager / GM (T+30 min)
            $table->timestamp('notified_l2_at')->nullable()->after('notified_l1_at');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['notified_l1_at', 'notified_l2_at']);
        });
    }
};
