<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLoginActivity;
use App\Services\UserLoginActivityLogger;
use App\Traits\ERPTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class MicrosoftAuthController extends Controller
{
    use ERPTrait;

    public function __construct(
        protected UserLoginActivityLogger $loginActivityLogger
    ) {}

    public function redirect()
    {
        // Stateless OAuth avoids session/state mismatches when APP_URL, SESSION_DOMAIN, or proxies
        // do not match the browser host (common for athena.fumaco.net vs APP_URL=localhost).
        return Socialite::driver('azure')->stateless()->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $socialUser = Socialite::driver('azure')->stateless()->user();

            $microsoftId = (string) ($socialUser->getId() ?? '');
            $displayName = (string) ($socialUser->getName() ?? '');
            $email = (string) ($socialUser->getEmail() ?? data_get($socialUser, 'user.userPrincipalName') ?? '');

            if (! $email) {
                $this->loginActivityLogger->record($request, UserLoginActivity::STATUS_FAILED, 'microsoft:missing_email', null);

                return redirect('/login')->withErrors('Microsoft sign-in did not return an email address.');
            }

            $normalizedEmail = $this->normalizeFumacoEmail($email);
            $candidateEmails = [
                $normalizedEmail,
                str_replace('@fumaco.com', '@fumaco.local', $normalizedEmail),
            ];

            $user = User::query()
                ->whereIn('wh_user', $candidateEmails)
                ->first();

            if (! $user) {
                $user = User::create([
                    'name' => (string) Str::uuid(),
                    'wh_user' => $normalizedEmail,
                    'frappe_userid' => $normalizedEmail,
                    'full_name' => $displayName ?: $normalizedEmail,
                    'password' => bcrypt(Str::random(48)),
                ]);
            }

            // tabWarehouse Users has no `email` column — identity is `wh_user` / `frappe_userid`.
            $user->forceFill([
                'microsoft_id' => $microsoftId ?: null,
                'microsoft_email' => $email,
                'microsoft_name' => $displayName ?: null,
                'full_name' => $user->full_name ?: ($displayName ?: null),
            ])->save();

            if (! $user->api_key || ! $user->api_secret) {
                $apiCredentials = $this->generateApiCredentials($user);
                if (! data_get($apiCredentials, 'success')) {
                    Auth::logout();
                    $this->loginActivityLogger->record($request, UserLoginActivity::STATUS_FAILED, $normalizedEmail, $user);

                    return redirect('/login')->withErrors((string) data_get($apiCredentials, 'message', 'Unable to generate ERP API credentials.'));
                }
            }

            Auth::login($user);

            User::where('name', $user->name)->update(['last_login' => now()->toDateTimeString()]);

            $this->loginActivityLogger->record($request, UserLoginActivity::STATUS_SUCCESS, $normalizedEmail, $user);

            return redirect('/');
        } catch (\Throwable $e) {
            Log::error('Microsoft SSO callback failed', [
                'message' => $e->getMessage(),
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $this->loginActivityLogger->record($request, UserLoginActivity::STATUS_FAILED, 'microsoft:error', null);

            return redirect('/login')->withErrors('Microsoft sign-in failed. Please try again.');
        }
    }

    private function normalizeFumacoEmail(string $email): string
    {
        $email = strtolower(trim($email));
        $localPart = Str::before($email, '@');

        return "{$localPart}@fumaco.com";
    }
}
