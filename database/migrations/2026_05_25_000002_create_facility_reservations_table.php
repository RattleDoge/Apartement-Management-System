<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 60)->unique();
            $table->string('unit', 30)->nullable();
            $table->string('tenant_name', 100)->nullable();
            $table->string('nama_fasilitas', 100)->nullable();
            $table->date('tanggal_reservasi')->nullable();
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->text('keperluan')->nullable();
            $table->unsignedSmallInteger('jumlah_tamu')->default(0);
            $table->boolean('is_berbayar')->default(false);
            $table->decimal('biaya', 12, 2)->default(0);
            $table->string('status_bayar', 30)->default('Bebas Biaya'); // Bebas Biaya / Belum Bayar / Sudah Bayar
            $table->string('bukti_bayar')->nullable();
            $table->string('status', 60)->default('Pesan Diterima');
            $table->string('request_by', 100)->nullable();
            $table->string('request_via', 50)->nullable();
            $table->text('catatan')->nullable();
            $table->string('foto')->nullable();
            // Dept checks — recorded independently, auto-advance to Siap when all done
            $table->string('cs_by', 100)->nullable();
            $table->timestamp('cs_at')->nullable();
            $table->string('fin_by', 100)->nullable();
            $table->timestamp('fin_at')->nullable();
            $table->string('hk_by', 100)->nullable();
            $table->timestamp('hk_at')->nullable();
            $table->string('eng_by', 100)->nullable();
            $table->timestamp('eng_at')->nullable();
            $table->string('sec_open_by', 100)->nullable();
            $table->timestamp('sec_open_at')->nullable();
            $table->string('sec_close_by', 100)->nullable();
            $table->timestamp('sec_close_at')->nullable();
            $table->text('sec_close_catatan')->nullable();
            $table->string('input_by', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_reservations');
    }
};
