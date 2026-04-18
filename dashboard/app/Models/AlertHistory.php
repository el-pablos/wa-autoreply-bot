<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertHistory extends Model
{
    use HasFactory;

    protected $table = 'alert_history';

    protected $fillable = [
        'channel_id',
        'severity',
        'message',
        'delivered_at',
        'success',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'success' => 'boolean',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(AlertChannel::class, 'channel_id');
    }
}
