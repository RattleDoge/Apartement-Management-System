<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_masters', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->nullable();
            $table->string('nama', 200);
            $table->string('satuan', 30)->default('unit');
            $table->unsignedBigInteger('harga')->default(0);
            $table->string('kategori', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_masters');
    }
};
