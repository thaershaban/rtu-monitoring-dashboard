<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RtuData extends Model
{
    use HasFactory;

    protected $table = 'rtu_data';

    protected $fillable = [
        'id',
        'address',
        'name',
        'status_rtu',
        'lastmodified',
        'status_Count',
        'status_hour',
        'status_day',
        'count_minutes',
        'count_days',
        'percentage_day',
    ];

    public $timestamps = false;
}
