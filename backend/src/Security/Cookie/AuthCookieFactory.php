<?php

declare(strict_types=1);

namespace App\Security\Cookie;

use Symfony\Component\HttpFoundation\Cookie;

final class AuthCookieFactory
{
    public const BEARER_TTL = 900;
    public const REFRESH_TTL = 604800;

    public function createBearer(string $jwt): Cookie
    {
        return Cookie::create('BEARER')
            ->withValue($jwt)
            ->withExpires(time() + self::BEARER_TTL)
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_STRICT);
    }

    public function createRefreshToken(string $token): Cookie
    {
        return Cookie::create('REFRESH_TOKEN')
            ->withValue($token)
            ->withExpires(time() + self::REFRESH_TTL)
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_STRICT);
    }

    public function clearBearer(): Cookie
    {
        return Cookie::create('BEARER')
            ->withValue('')
            ->withExpires(1)
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_STRICT);
    }

    public function clearRefreshToken(): Cookie
    {
        return Cookie::create('REFRESH_TOKEN')
            ->withValue('')
            ->withExpires(1)
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_STRICT);
    }
}
