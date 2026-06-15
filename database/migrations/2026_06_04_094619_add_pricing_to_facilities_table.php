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
        Schema::table('facilities', function (Blueprint $table) {
            $table->boolean('is_berbayar')->default(false)->after('check_billing');
            $table->decimal('biaya', 15, 2)->default(0)->after('is_berbayar');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn(['is_berbayar', 'biaya']);
        });
    }
};
