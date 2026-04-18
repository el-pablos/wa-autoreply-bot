<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiConversationHistory extends Model
{
    use HasFactory;

    protected $table = 'ai_conversation_history';

    protected $fillable = [
        'phone_number',
        'role',
        'content',
        'tokens',
    ];

    protected $casts = [
        'tokens' => 'integer',
    ];
}
