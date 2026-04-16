<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovedSession extends Model
{
    protected $table = 'approved_sessions';

    protected $fillable = [
        'phone_number',
        'approved_at',
        'last_activity_at',
        'expires_at',
        'is_active',
        'approved_by',
        'revoked_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
