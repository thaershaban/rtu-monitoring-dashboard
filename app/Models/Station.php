<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    use HasFactory;

    protected $table = 'stations_new'; // يشير إلى الجدول الجديد

    // هذا هو التعديل الحاسم: نحدد أن المفتاح الأساسي هو 'station_id'
    protected $primaryKey = 'station_id';

    // بما أن 'station_id' ليس تلقائي التزايد في سياق استخدامنا له كمعرف، نحدد ذلك.
    public $incrementing = false;

    // نحدد نوع المفتاح الأساسي (عادة ما يكون عدد صحيح)
    protected $keyType = 'int';

    // بما أن `created_at` و `updated_at` لا يتم إدارتهما تلقائياً بالكامل من قبل Laravel/DB بسبب قيود الإصدار القديم،
    // سنعطل ميزة timestamps التلقائية في Eloquent.
    public $timestamps = false;

    // الأعمدة التي يمكن تعبئتها جماعياً (mass assignable)
    protected $fillable = [
        'id', // لا يزال يمكننا تضمين 'id' هنا إذا كنا نستخدمه في أي عمليات أخرى
        'station_id',
        'name',
        'arabic_name',
        'substation_name',
        'created_at',
        'updated_at',
    ];

    // يمكنك إضافة هذا إذا أردت التأكد من تحويل التاريخ/الوقت بشكل صحيح
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
