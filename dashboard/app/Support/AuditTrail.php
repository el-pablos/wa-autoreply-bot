<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditTrail
{
    public static function record(
        Request $request,
        string $action,
        Model|array|null $target = null,
        mixed $oldValue = null,
        mixed $newValue = null,
        ?string $actor = null
    ): void {
        $resolvedActor = $actor ?? (string) optional($request->user())->email;
        if ($resolvedActor === '') {
            $resolvedActor = 'system';
        }

        ActivityLog::record(
            $resolvedActor,
            $action,
            $target,
            $oldValue,
            $newValue,
            $request->ip(),
            self::trimUserAgent($request->userAgent())
        );
    }

    private static function trimUserAgent(?string $userAgent): ?string
    {
        if ($userAgent === null || $userAgent === '') {
            return null;
        }

        return mb_substr($userAgent, 0, 255);
    }
}
