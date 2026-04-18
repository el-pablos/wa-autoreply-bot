<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsDailySummary extends Model
{
    use HasFactory;

    protected $table = 'analytics_daily_summary';
    protected $primaryKey = 'date';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'date',
        'messages_in',
        'messages_out',
        'avg_response_ms',
        'top_numbers',
    ];

    protected $casts = [
        'date' => 'date',
        'messages_in' => 'integer',
        'messages_out' => 'integer',
        'avg_response_ms' => 'integer',
        'top_numbers' => 'array',
    ];
}
