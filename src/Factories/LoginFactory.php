<?php

namespace ALajusticia\Logins\Factories;

use ALajusticia\Logins\Models\Login;
use ALajusticia\Logins\RequestContext;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\PersonalAccessToken;

class LoginFactory
{
    public static function buildFromLogin(
        RequestContext $context,
        string $sessionId,
        AuthenticatableContract $user,
        bool $remember,
    ): Login
    {
        $login = self::getNewLogin($context);

        $login->fill([
            'session_id' => $sessionId,
            'remember_token' => $remember ? $user->getRememberToken() : null,
        ]);

        // Set the expiration date based on whether it is a remembered login or not
        if ($remember) {
            if ($rememberTokenLifetime = Config::get('logins.remember_token_lifetime')) {
                $login->expiresAt(Carbon::now()->addDays($rememberTokenLifetime));
            } else {
                $login->expiresAt(null);
            }
        } else {
            $login->expiresAt(Carbon::now()->addMinutes(config('session.lifetime')));
        }

        return $login;
    }

    public static function buildFromSanctumToken(
        RequestContext $context,
        PersonalAccessToken $token
    ): Login
    {
        $login = self::getNewLogin($context);

        $login->personal_access_token_id = $token->id;

        return $login;
    }

    protected static function getNewLogin(RequestContext $context): Login
    {
        return new Login([
            'user_agent' => $context->userAgent(),
            'ip_address' => $context->ipAddress(),
            'device_type' => $context->parser()->getDeviceType(),
            'device' => $context->parser()->getDevice(),
            'platform' => $context->parser()->getPlatform(),
            'browser' => $context->parser()->getBrowser(),
            'location' => $context->location() ?? null,
        ]);
    }
}
