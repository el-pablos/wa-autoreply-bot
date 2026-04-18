<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookEndpoint extends Model
{
    use HasFactory;

    protected $table = 'webhook_endpoints';

    protected $fillable = [
        'name',
        'url',
        'secret',
        'events',
        'is_active',
        'last_triggered_at',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    public function deliveryLogs(): HasMany
    {
        return $this->hasMany(WebhookDeliveryLog::class, 'endpoint_id');
    }
}
