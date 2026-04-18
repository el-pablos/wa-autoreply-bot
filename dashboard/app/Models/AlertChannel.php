<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlertChannel extends Model
{
    use HasFactory;

    protected $table = 'alert_channels';

    protected $fillable = [
        'type',
        'target',
        'is_active',
        'last_alert_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_alert_at' => 'datetime',
    ];

    public function history(): HasMany
    {
        return $this->hasMany(AlertHistory::class, 'channel_id');
    }
}
