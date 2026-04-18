<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReplyTemplate extends Model
{
    use HasFactory;

    protected $table = 'reply_templates';

    protected $fillable = [
        'name',
        'body',
        'is_default',
        'conditions_json',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'conditions_json' => 'array',
    ];
}
