<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->boolean('is_berbayar')->default(false)->after('status_comp');
            $table->decimal('biaya_wo', 12, 2)->nullable()->after('is_berbayar');
            $table->text('keterangan_biaya')->nullable()->after('biaya_wo');
            $table->string('fin_by', 100)->nullable()->after('keterangan_biaya');
            $table->timestamp('fin_at')->nullable()->after('fin_by');
            $table->string('fin_status', 30)->nullable()->after('fin_at'); // Approved / Rejected
            $table->text('fin_notes')->nullable()->after('fin_status');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn([
                'is_berbayar', 'biaya_wo', 'keterangan_biaya',
                'fin_by', 'fin_at', 'fin_status', 'fin_notes',
            ]);
        });
    }
};
