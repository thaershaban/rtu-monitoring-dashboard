<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RtuDailyPerformance extends Model
{
    use HasFactory;

    protected $table = 'rtu_daily_performance';

    protected $fillable = [
        'station_id',
        'performance_date',
        'daily_operation_percentage',
        'total_sum_for_day',
        'total_minutes_logged',
        'daily_normal_count',
        'daily_failed_count',
        'daily_marginal_count',
        'daily_alarm_count',
    ];

    protected $casts = [
        'performance_date' => 'date',
    ];
}