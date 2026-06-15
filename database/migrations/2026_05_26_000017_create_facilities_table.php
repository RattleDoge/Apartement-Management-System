<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('nama_fasilitas');
            $table->unsignedInteger('max_pengunjung')->nullable();
            $table->unsignedInteger('jumlah_orang')->nullable();
            $table->string('durasi', 8)->default('01:00');
            $table->string('max_terlambat', 8)->default('00:30');
            $table->string('min_hadir', 8)->default('00:10');
            $table->string('open_fasilitas', 8)->default('08:00');
            $table->string('close_fasilitas', 8)->default('18:00');
            $table->enum('check_billing', ['Aktif', 'Tidak Aktif'])->default('Tidak Aktif');
            $table->string('icon')->nullable();
            $table->text('terms')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
