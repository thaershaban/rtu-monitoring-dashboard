<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RtuArchive extends Model
{
    use HasFactory;

    protected $table = 'rtu_archive'; // اسم الجدول في قاعدة البيانات

    protected $fillable = [
        'station_id',
        'arabic_name',
        'substation_name',
        'total_value',
        'status_code',
        'connection_status',
        'last_seen',
    ];

    // تحديد نوع البيانات لبعض الأعمدة
    protected $casts = [
        'last_seen' => 'datetime',
    ];
}