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
        Schema::create('rtu_daily_performance', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('station_id')->unsigned(); // معرف المحطة
            $table->date('performance_date');             // تاريخ الأداء
            $table->integer('total_readings')->default(0);
            $table->float('weighted_sum')->default(0);
            $table->float('performance_percentage')->default(0);
            $table->timestamps(); // created_at, updated_at

            $table->unique(['station_id', 'performance_date']); // لضمان سجل واحد لكل RTU في اليوم
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rtu_daily_performance');
    }
};