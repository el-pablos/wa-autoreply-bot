<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RateLimitViolation extends Model
{
    use HasFactory;

    protected $table = 'rate_limit_violations';

    protected $fillable = [
        'phone_number',
        'window_start',
        'message_count',
    ];

    protected $casts = [
        'window_start'  => 'datetime',
        'message_count' => 'integer',
    ];
}
