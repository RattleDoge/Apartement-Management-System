<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('nama_staff');
            $table->string('departemen', 50)->nullable();
            $table->enum('status', ['Aktif', 'Non-aktif'])->default('Aktif');
            $table->string('no_hp_otp', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('role', 50)->nullable();
            $table->string('finger_id', 30)->nullable();
            $table->string('pt', 20)->default('MAP');
            $table->string('project', 20)->default('MAP');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
