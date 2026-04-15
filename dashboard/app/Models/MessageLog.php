<?php
// app/Models/MessageLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageLog extends Model
{
    protected $table      = 'message_logs';
    public    $timestamps = false;
    protected $fillable   = [
        'from_number', 'message_text', 'message_type',
        'is_allowed', 'replied', 'reply_text', 'group_id',
    ];
    protected $casts = [
        'is_allowed'  => 'boolean',
        'replied'     => 'boolean',
        'received_at' => 'datetime',
    ];
}
