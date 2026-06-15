<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('ex_in', 5)->default('IN');       // IN = internal, EX = external
            $table->string('no_complain', 60)->nullable();    // no. komplain dari tenant
            $table->string('no_wo', 60)->unique();            // nomor work order
            $table->string('jenis_wo', 100);
            $table->string('sub_jenis_wo', 100)->nullable();
            $table->datetime('tanggal');
            $table->date('estimated_close')->nullable();
            $table->string('lot_no', 50)->nullable();         // nomor unit
            $table->string('name', 150)->nullable();          // nama tenant / pengirim
            $table->text('descs')->nullable();                // deskripsi
            $table->string('status_comp', 60)->nullable();    // status penyelesaian
            $table->string('durasi', 60)->nullable();
            $table->string('durasi_bln', 30)->default('kurang1bln');
            $table->string('request_by', 100)->nullable();
            $table->string('request_via', 50)->nullable();    // Phone, WA, Letter, Visit, dll
            $table->string('assign_dep', 50)->nullable();     // ENG, CS, SEC, HK, FIN
            $table->string('assign_staff', 150)->nullable();
            $table->json('item_service')->nullable();         // [{nama, harga, qty}]
            $table->string('input_by', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
