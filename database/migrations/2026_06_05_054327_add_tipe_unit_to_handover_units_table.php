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
            $table->string('tipe_unit', 20)->nullable()->after('lot_no');
        });
    }

    public function down(): void
    {
        Schema::table('handover_units', function (Blueprint $table) {
            $table->dropColumn('tipe_unit');
        });
    }
};
