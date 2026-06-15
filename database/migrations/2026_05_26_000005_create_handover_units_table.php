<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('handover_units', function (Blueprint $table) {
            $table->id();
            $table->string('lot_no', 30)->unique()->index();
            $table->date('str_date')->nullable();
            $table->date('cmg_date')->nullable();
            $table->string('pic', 100)->nullable();

            // Completeness checkboxes
            $table->boolean('ppjb')->default(false);
            $table->boolean('bast')->default(false);
            $table->boolean('house_rule')->default(false);

            // IPL+SF payment tracking
            $table->date('ipl_sf_paydate')->nullable();
            $table->string('ipl_sf_period', 30)->nullable(); // "for Periode"
            $table->unsignedSmallInteger('until_month')->default(0);
            $table->unsignedSmallInteger('next_month')->default(0);

            // Physical items issued
            $table->unsignedTinyInteger('key_count')->default(0);
            $table->unsignedTinyInteger('access_card_count')->default(0);

            // Reference numbers
            $table->string('no_access_card', 60)->nullable();
            $table->string('no_intercom', 60)->nullable();
            $table->string('no_telpon', 30)->nullable();

            $table->string('input_by', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handover_units');
    }
};
