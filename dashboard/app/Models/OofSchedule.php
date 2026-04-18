<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OofSchedule extends Model
{
    use HasFactory;

    protected $table = 'oof_schedules';

    protected $fillable = [
        'start_date',
        'end_date',
        'message',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];
}
