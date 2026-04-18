<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    use HasFactory;

    protected $table = 'backups';

    protected $fillable = [
        'path',
        'size_bytes',
        'type',
        'checksum',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];
}
