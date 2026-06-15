<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('in_out_permits', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 60)->unique();
            $table->string('unit', 30)->nullable();
            $table->string('tenant_name', 100)->nullable();
            $table->date('tanggal')->nullable();
            $table->date('tanggal_ijin')->nullable();
            $table->time('jam')->nullable();
            $table->string('jenis', 20)->nullable();          // Masuk / Keluar
            $table->text('descs')->nullable();
            $table->string('request_by', 100)->nullable();
            $table->string('request_via', 50)->nullable();
            $table->string('status', 60)->default('Pesan Diterima');
            $table->string('foto')->nullable();
            $table->string('approved_cs_by', 100)->nullable();
            $table->timestamp('approved_cs_at')->nullable();
            $table->string('approved_fa_by', 100)->nullable();
            $table->timestamp('approved_fa_at')->nullable();
            $table->string('approved_sec_by', 100)->nullable();
            $table->timestamp('approved_sec_at')->nullable();
            $table->string('input_by', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('in_out_permits');
    }
};
