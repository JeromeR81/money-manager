<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\AccessToken\AccessTokenExtractorInterface;

final class CookieTokenExtractor implements AccessTokenExtractorInterface
{
    public function extractAccessToken(Request $request): ?string
    {
        return $request->cookies->get('BEARER') ?: null;
    }
}
