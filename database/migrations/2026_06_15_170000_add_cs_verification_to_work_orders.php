<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->string('cs_status', 30)->nullable()->after('tgl_bukti_bayar_wo'); // Verified | Rejected
            $table->string('cs_by', 100)->nullable()->after('cs_status');
            $table->timestamp('cs_at')->nullable()->after('cs_by');
            $table->text('cs_notes')->nullable()->after('cs_at');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['cs_status', 'cs_by', 'cs_at', 'cs_notes']);
        });
    }
};
