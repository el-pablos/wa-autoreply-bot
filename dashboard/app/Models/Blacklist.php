<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blacklist extends Model
{
    use HasFactory;

    protected $table = 'blacklist';

    protected $fillable = [
        'phone_number',
        'reason',
        'blocked_at',
        'unblock_at',
        'blocked_by',
        'is_active',
    ];

    protected $casts = [
        'blocked_at' => 'datetime',
        'unblock_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
