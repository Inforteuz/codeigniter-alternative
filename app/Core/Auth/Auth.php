<?php

namespace App\Core\Auth;

use App\Core\Container;

/**
 * Auth Facade
 * 
 * Provides static access to the AuthManager bound in the Container.
 */
class Auth
{
    protected static function getManager(): AuthManager
    {
        return Container::getInstance()->make(AuthManager::class);
    }

    public static function attempt(array $credentials): bool
    {
        return static::getManager()->attempt($credentials);
    }

    public static function login($user)
    {
        return static::getManager()->login($user);
    }

    public static function logout()
    {
        return static::getManager()->logout();
    }

    public static function check(): bool
    {
        return static::getManager()->check();
    }

    public static function user()
    {
        return static::getManager()->user();
    }

    public static function id()
    {
        return static::getManager()->id();
    }
}
