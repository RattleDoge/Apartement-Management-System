<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('handover_units', function (Blueprint $table) {
            $table->boolean('billing_aktif')->default(true)->after('input_by');
        });
    }

    public function down(): void
    {
        Schema::table('handover_units', function (Blueprint $table) {
            $table->dropColumn('billing_aktif');
        });
    }
};
