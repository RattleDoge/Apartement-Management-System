<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_reservations', function (Blueprint $table) {
            $table->string('qr_token', 64)->nullable()->unique()->after('sec_close_catatan');
        });
    }

    public function down(): void
    {
        Schema::table('facility_reservations', function (Blueprint $table) {
            $table->dropColumn('qr_token');
        });
    }
};
