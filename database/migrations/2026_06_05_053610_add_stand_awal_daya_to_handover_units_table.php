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
        Schema::table('handover_units', function (Blueprint $table) {
            $table->string('daya_listrik', 20)->nullable()->after('no_telpon');
            $table->decimal('stand_awal_listrik', 10, 2)->nullable()->after('daya_listrik');
            $table->decimal('stand_awal_air', 10, 2)->nullable()->after('stand_awal_listrik');
        });
    }

    public function down(): void
    {
        Schema::table('handover_units', function (Blueprint $table) {
            $table->dropColumn(['daya_listrik', 'stand_awal_listrik', 'stand_awal_air']);
        });
    }
};
