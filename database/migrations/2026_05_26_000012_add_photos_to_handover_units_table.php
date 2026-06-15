<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('handover_units', function (Blueprint $table) {
            $table->string('pas_foto')->nullable()->after('no_telpon');
            $table->string('foto_ktp')->nullable()->after('pas_foto');
        });
    }

    public function down(): void
    {
        Schema::table('handover_units', function (Blueprint $table) {
            $table->dropColumn(['pas_foto', 'foto_ktp']);
        });
    }
};
