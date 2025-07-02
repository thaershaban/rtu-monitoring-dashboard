<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RtuMinuteLog extends Model
{
    use HasFactory;

    protected $table = 'rtu_minute_logs';

    protected $fillable = [
        'station_id',
        'log_time',
        'total_value',
        'status_code',
        'status_description',
        'connection_status',
    ];

    protected $casts = [
        'log_time' => 'datetime',
    ];
}