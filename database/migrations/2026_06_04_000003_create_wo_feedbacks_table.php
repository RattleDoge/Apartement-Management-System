<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wo_feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('work_order_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->tinyInteger('rating')->default(0);
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wo_feedback');
    }
};
