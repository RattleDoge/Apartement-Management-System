<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_requests', function (Blueprint $table) {
            $table->date('tgl_dalam_proses')->nullable()->after('tgl_verifikasi');
            $table->date('tgl_selesai')->nullable()->after('tgl_dalam_proses');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_requests', function (Blueprint $table) {
            $table->dropColumn(['tgl_dalam_proses', 'tgl_selesai']);
        });
    }
};
