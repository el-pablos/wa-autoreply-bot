<?php
// app/Models/AllowedNumber.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AllowedNumber extends Model
{
    protected $table    = 'allowed_numbers';
    protected $fillable = ['phone_number', 'label', 'is_active'];
    protected $casts    = ['is_active' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
