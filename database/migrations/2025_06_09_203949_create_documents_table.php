<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path'); // المسار الفعلي للملف على القرص
            $table->string('file_name'); // اسم الملف الأصلي
            $table->string('file_mime_type')->nullable(); // نوع الملف (مثل application/pdf)
            $table->unsignedBigInteger('file_size')->nullable(); // حجم الملف بالبايت
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};