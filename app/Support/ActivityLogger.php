<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

final class ActivityLogger
{
    public static function log(string $action, ?string $description = null, ?User $user = null): void
    {
        try {
            $user = $user ?? Auth::user();

            $ipAddress = request() ? request()->ip() : null;
            $userAgent = request() ? request()->userAgent() : null;

            ActivityLog::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'action' => $action,
                'description' => $description,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        } catch (\Throwable $e) {
            Log::error('ActivityLogger failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }
}
