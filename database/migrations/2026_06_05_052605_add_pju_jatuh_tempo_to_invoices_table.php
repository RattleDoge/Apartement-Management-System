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
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('jatuh_tempo')->nullable()->after('inv_date');
            $table->decimal('pju_amount', 10, 2)->nullable()->after('listrik_amount');
            $table->decimal('biaya_tambahan', 10, 2)->nullable()->after('pju_amount');
            $table->decimal('beban_tetap', 10, 2)->nullable()->after('biaya_tambahan');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['jatuh_tempo', 'pju_amount', 'biaya_tambahan', 'beban_tetap']);
        });
    }
};
