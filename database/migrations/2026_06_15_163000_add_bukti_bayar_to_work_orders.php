<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->string('bukti_bayar_wo')->nullable()->after('fin_notes');
            $table->timestamp('tgl_bukti_bayar_wo')->nullable()->after('bukti_bayar_wo');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['bukti_bayar_wo', 'tgl_bukti_bayar_wo']);
        });
    }
};
