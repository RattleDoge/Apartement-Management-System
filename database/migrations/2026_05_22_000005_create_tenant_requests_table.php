<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_requests', function (Blueprint $table) {
            $table->id();
            $table->string('no_request')->unique();
            $table->timestamp('tanggal')->nullable();
            $table->date('tgl_verifikasi')->nullable();
            $table->string('lot_no')->nullable();
            $table->string('nama')->nullable();
            $table->string('kepemilikan')->nullable();
            $table->string('sales_agent')->nullable();
            $table->string('kategori')->nullable();
            $table->string('sub_kategori')->nullable();
            $table->string('pelaporan_via')->nullable();
            $table->text('descs')->nullable();
            $table->string('request_by')->nullable();
            $table->string('status')->nullable();
            $table->string('berulang')->default('Tidak');
            $table->date('tgl_str')->nullable();
            $table->text('desc_status')->nullable();
            $table->string('input_by')->nullable();
            $table->boolean('is_selesai')->default(false);
            $table->text('alasan_tidak_aplikasi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_requests');
    }
};
