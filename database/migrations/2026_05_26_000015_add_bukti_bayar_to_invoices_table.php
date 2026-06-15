<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('bukti_bayar')->nullable()->after('paid_by');
            $table->dateTime('tgl_bukti_bayar')->nullable()->after('bukti_bayar');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['bukti_bayar', 'tgl_bukti_bayar']);
        });
    }
};
