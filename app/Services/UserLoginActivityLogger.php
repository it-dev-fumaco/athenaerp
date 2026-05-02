<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserLoginActivity;
use Illuminate\Http\Request;

class UserLoginActivityLogger
{
    public function record(?Request $request, string $status, ?string $username = null, ?User $user = null): void
    {
        if (! in_array($status, [UserLoginActivity::STATUS_SUCCESS, UserLoginActivity::STATUS_FAILED], true)) {
            return;
        }

        $request ??= request();

        $resolvedUsername = $username ?? (string) ($user?->wh_user ?? '');
        if ($resolvedUsername === '') {
            $resolvedUsername = 'unknown';
        }

        UserLoginActivity::query()->create([
            'user_id' => $user?->name,
            'username' => mb_substr($resolvedUsername, 0, 500),
            'login_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => $status,
        ]);
    }
}
