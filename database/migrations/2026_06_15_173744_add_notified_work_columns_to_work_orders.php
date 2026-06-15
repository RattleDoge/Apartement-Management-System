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
        Schema::table('work_orders', function (Blueprint $table) {
            // Eskalasi untuk WO yang sudah assigned tapi work_started masih null
            $table->timestamp('notified_work_l1_at')->nullable()->after('notified_l2_at');
            $table->timestamp('notified_work_l2_at')->nullable()->after('notified_work_l1_at');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['notified_work_l1_at', 'notified_work_l2_at']);
        });
    }
};
