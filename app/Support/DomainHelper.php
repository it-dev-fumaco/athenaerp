<?php

namespace App\Support;

use Illuminate\Http\Request;

class DomainHelper
{
    public static function isSsoOnlyHost(?Request $request = null): bool
    {
        $request ??= request();

        return $request->getHost() === config('auth.sso_domain');
    }

    public static function isPasswordLoginHost(?Request $request = null): bool
    {
        return ! self::isSsoOnlyHost($request);
    }
}
