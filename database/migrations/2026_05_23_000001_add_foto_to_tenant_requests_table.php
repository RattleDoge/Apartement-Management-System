<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_requests', function (Blueprint $table) {
            $table->string('foto')->nullable()->after('alasan_tidak_aplikasi');
            $table->string('done_by')->nullable()->after('foto');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_requests', function (Blueprint $table) {
            $table->dropColumn(['foto', 'done_by']);
        });
    }
};
