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
        Schema::create('rtu_archive', function (Blueprint $table) {
            $table->id(); // يضيف عمود 'id' كـ Primary Key و Auto-Increment
            $table->bigInteger('station_id')->unsigned()->unique(); // معرف المحطة، UNIQUE
            
            // الأعمدة الإضافية التي قد تكون ثابتة لكل RTU أو تأتي من مصدر آخر
            $table->string('arabic_name')->nullable();       // اسم RTU باللغة العربية
            $table->string('substation_name')->nullable();   // اسم المحطة الفرعية
            $table->string('connection_status')->nullable(); // حالة الاتصال (مثلاً 'In' أو 'Out')

            // الأعمدة التي سيتم تحديثها من datascada بواسطة الـ Event
            $table->float('total_value')->default(0);        // القيمة التراكمية، تبدأ من 0
            $table->integer('status_code')->nullable();      // كود الحالة
            $table->dateTime('last_seen')->nullable();       // آخر وقت تم فيه رؤية الجهاز (من lastmodified في datascada)
            
            $table->timestamps(); // يضيف عمودي 'created_at' و 'updated_at'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rtu_archive');
    }
};