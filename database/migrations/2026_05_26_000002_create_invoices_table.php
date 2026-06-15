<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            // Period
            $table->unsignedTinyInteger('bulan')->index();
            $table->unsignedSmallInteger('tahun')->index();

            // Invoice identity
            $table->string('no_invoice', 60)->unique();
            $table->date('inv_date');
            $table->string('debtor_acct', 50)->index();   // unit number
            $table->string('debtor_name', 100);
            $table->string('kategori', 30)->default('IPL+Listrik+Air');
            $table->text('description')->nullable();

            // IPL (maintenance fee)
            $table->decimal('ipl_amount', 12, 2)->default(0);

            // Electricity
            $table->decimal('kwh_prev', 10, 3)->nullable();       // previous meter reading
            $table->decimal('kwh_curr', 10, 3)->nullable();       // current meter reading
            $table->decimal('kwh_used', 10, 3)->nullable();       // kwh_curr - kwh_prev
            $table->string('daya_terpasang', 20)->nullable();     // installed capacity, e.g. "2200 VA"
            $table->decimal('kwh_tariff', 10, 2)->nullable();     // tariff per kWh
            $table->decimal('listrik_amount', 12, 2)->default(0);

            // Water
            $table->decimal('meter_prev', 10, 3)->nullable();
            $table->decimal('meter_curr', 10, 3)->nullable();
            $table->decimal('meter_m3', 10, 3)->nullable();       // meter_curr - meter_prev
            $table->decimal('water_tariff', 10, 2)->nullable();
            $table->decimal('air_amount', 12, 2)->default(0);

            // Other charges
            $table->decimal('denda', 12, 2)->default(0);
            $table->decimal('other_charges', 12, 2)->default(0);

            // Total
            $table->decimal('amount', 12, 2)->default(0);

            // Contact
            $table->string('handphone', 25)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('virtual_account', 60)->nullable();

            // Payment tracking
            $table->string('status_bayar', 30)->default('Belum Lunas');
            $table->date('tgl_bayar')->nullable();
            $table->string('paid_by', 100)->nullable();

            // Upload metadata
            $table->string('uploaded_by', 100)->nullable();
            $table->string('upload_batch', 60)->nullable()->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
