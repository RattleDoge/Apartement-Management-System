<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preventive_maintenances', function (Blueprint $table) {
            $table->id();
            $table->string('judul', 200);
            $table->string('area', 100)->nullable();
            $table->date('tanggal');
            $table->string('jam_mulai', 10)->nullable();
            $table->string('jam_selesai', 10)->nullable();
            $table->string('penanggung_jawab', 100)->nullable();
            $table->string('status', 30)->default('Terjadwal');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preventive_maintenances');
    }
};
