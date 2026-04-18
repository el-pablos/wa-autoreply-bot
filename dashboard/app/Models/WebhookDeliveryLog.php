<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDeliveryLog extends Model
{
    use HasFactory;

    protected $table = 'webhook_delivery_logs';

    protected $fillable = [
        'endpoint_id',
        'event',
        'payload',
        'status',
        'response_code',
        'attempts',
        'response_body',
    ];

    protected $casts = [
        'payload' => 'array',
        'response_code' => 'integer',
        'attempts' => 'integer',
    ];

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'endpoint_id');
    }
}
