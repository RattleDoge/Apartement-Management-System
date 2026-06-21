<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_reservations', function (Blueprint $table) {
            $table->timestamp('notified_cs_at')->nullable()->after('sec_close_catatan');
            $table->timestamp('notified_am_at')->nullable()->after('notified_cs_at');
        });
    }

    public function down(): void
    {
        Schema::table('facility_reservations', function (Blueprint $table) {
            $table->dropColumn(['notified_cs_at', 'notified_am_at']);
        });
    }
};
