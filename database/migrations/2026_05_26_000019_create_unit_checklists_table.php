<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_checklists', function (Blueprint $table) {
            $table->id();
            $table->string('lot_no');
            $table->string('tenant_name')->nullable();
            $table->date('checklist_date');
            $table->text('defect')->nullable();
            $table->string('no_mtr_water', 50)->nullable();
            $table->string('current_read', 50)->nullable();
            $table->date('first_water_invoice')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_checklists');
    }
};
