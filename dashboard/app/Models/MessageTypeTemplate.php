<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageTypeTemplate extends Model
{
    use HasFactory;

    protected $table = 'message_type_templates';
    protected $primaryKey = 'message_type';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'message_type',
        'body',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
