<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EscalationLog extends Model
{
    use HasFactory;

    protected $table = 'escalation_logs';

    public $timestamps = false;

    protected $fillable = [
        'from_number',
        'trigger_reason',
        'escalated_to',
        'message_snippet',
        'escalated_at',
    ];

    protected $casts = [
        'escalated_at' => 'datetime',
    ];
}
