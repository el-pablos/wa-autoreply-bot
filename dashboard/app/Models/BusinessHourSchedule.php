<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessHourSchedule extends Model
{
    use HasFactory;

    protected $table = 'business_hour_schedules';

    protected $fillable = [
        'weekday',
        'start_time',
        'end_time',
        'timezone',
        'is_active',
    ];

    protected $casts = [
        'weekday' => 'integer',
        'is_active' => 'boolean',
    ];
}
