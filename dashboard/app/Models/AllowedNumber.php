<?php
// app/Models/AllowedNumber.php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class AllowedNumber extends Model
{
    protected $table    = 'allowed_numbers';
    protected $fillable = ['phone_number', 'label', 'template_id', 'is_active', 'reply_count_today', 'last_reply_at'];
    protected $casts    = [
        'is_active' => 'boolean',
        'reply_count_today' => 'integer',
        'last_reply_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReplyTemplate::class, 'template_id');
    }
}
