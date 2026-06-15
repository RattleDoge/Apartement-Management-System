<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('label')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        // Seed defaults
        $defaults = \App\Models\AppSetting::defaults();
        foreach ($defaults as $row) {
            \App\Models\AppSetting::create($row);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
