<?php

namespace ALajusticia\Logins\Traits;

use ALajusticia\Logins\CurrentLogin;
use ALajusticia\Logins\Models\Login;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

trait HasLogins
{
    /**
     * Get all the user's logins.
     */
    public function logins(): MorphMany
    {
        return $this->morphMany(Login::class, 'authenticatable');
    }

    /**
     * Get the current user's login.
     */
    public function getCurrentLoginAttribute(): ?Login
    {
        return app(CurrentLogin::class)->currentLogin;
    }

    /**
     * Destroy a session / Revoke an access token by its ID.
     *
     * @throws \Exception
     */
    public function logout(?int $loginId = null): bool
    {
        $login = $loginId ? $this->logins()->find($loginId) : $this->current_login;

        return $login ? !empty($login->revoke()) : false;
    }

    /**
     * Destroy all sessions / Revoke all sanctum tokens, except the current one.
     */
    public function logoutOthers(): mixed
    {
        if ($this->isAuthenticatedBySession()) {

            return $this->logins()
                        ->where(function (Builder $query) {
                            return $query
                                ->where('session_id', '!=', session()->getId())
                                ->orWhereNull('session_id');
                        })
                        ->revoke();

        } elseif ($this->isAuthenticatedBySanctumToken()) {

            return $this->logins()
                        ->where(function (Builder $query) {
                            return $query
                                ->where('personal_access_token_id', '!=', $this->currentAccessToken()->id)
                                ->orWhereNull('personal_access_token_id');
                        })
                        ->revoke();
        }

        return false;
    }

    /**
     * Destroy all sessions / Revoke all access tokens.
     */
    public function logoutAll(): mixed
    {
        return $this->logins()->revoke();
    }

    /**
     * Determine if current user is authenticated via a session.
     */
    public function isAuthenticatedBySession(): bool
    {
        return request()->hasSession()
            && ! is_null(request()->user())
            && Auth::check();
    }

    /**
     * Check for authentication via Sanctum.
     */
    public function isAuthenticatedBySanctumToken(): bool
    {
        return in_array('Laravel\Sanctum\HasApiTokens', class_uses_recursive($this))
            && ! is_null($this->currentAccessToken());
    }
}