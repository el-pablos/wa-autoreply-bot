<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_logs';

    protected $fillable = [
        'actor',
        'action',
        'target_type',
        'target_id',
        'old_value',
        'new_value',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
    ];

    /**
     * Helper static untuk langsung record aktivitas.
     *
     * @param  mixed  $target  Bisa Eloquent Model atau null.
     */
    public static function record(
        string $actor,
        string $action,
        $target = null,
        $old = null,
        $new = null,
        ?string $ip = null,
        ?string $ua = null
    ): self {
        $targetType = null;
        $targetId   = null;

        if ($target instanceof Model) {
            $targetType = $target::class;
            $targetId   = $target->getKey();
        } elseif (is_array($target)) {
            $targetType = $target['type'] ?? null;
            $targetId   = $target['id']   ?? null;
        }

        return static::create([
            'actor'       => $actor,
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'old_value'   => $old,
            'new_value'   => $new,
            'ip_address'  => $ip,
            'user_agent'  => $ua,
        ]);
    }
}
