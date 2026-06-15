<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->text('balas_request')->nullable()->after('input_by');
            $table->string('balas_by', 100)->nullable()->after('balas_request');
            $table->datetime('balas_at')->nullable()->after('balas_by');
            $table->datetime('work_started')->nullable()->after('balas_at');
            $table->datetime('work_closed')->nullable()->after('work_started');
            $table->string('action_by', 100)->nullable()->after('work_closed');
            $table->text('action_taken')->nullable()->after('action_by');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn([
                'balas_request', 'balas_by', 'balas_at',
                'work_started', 'work_closed', 'action_by', 'action_taken',
            ]);
        });
    }
};
