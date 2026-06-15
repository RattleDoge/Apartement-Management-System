<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('greeting_templates', function (Blueprint $table) {
            $table->id();
            $table->string('nama_template');
            $table->string('jenis')->default('News Announcement');
            $table->longText('isi')->nullable();
            $table->string('cover_img')->nullable();
            $table->string('content_img')->nullable();
            $table->string('status')->default('Active');
            $table->string('modified_by')->nullable();
            $table->unsignedInteger('kirim_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('greeting_templates');
    }
};
