<?php
// app/Models/BotSetting.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotSetting extends Model
{
    protected $table      = 'bot_settings';
    protected $primaryKey = 'key';
    public    $incrementing = false;
    public    $keyType    = 'string';
    public    $timestamps = false;
    protected $fillable   = ['key', 'value', 'description'];

    /**
     * Helper: ambil value berdasarkan key.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::find($key);
        return $setting ? $setting->value : $default;
    }

    /**
     * Helper: set value berdasarkan key.
     */
    public static function setValue(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
