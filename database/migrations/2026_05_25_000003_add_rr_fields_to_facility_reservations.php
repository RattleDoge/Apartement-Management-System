<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_reservations', function (Blueprint $table) {
            // Round Robin assignment fields
            $table->unsignedTinyInteger('rr_index')->nullable()->after('input_by');
            $table->string('rr_officer', 100)->nullable()->after('rr_index');
        });
    }

    public function down(): void
    {
        Schema::table('facility_reservations', function (Blueprint $table) {
            $table->dropColumn(['rr_index', 'rr_officer']);
        });
    }
};
