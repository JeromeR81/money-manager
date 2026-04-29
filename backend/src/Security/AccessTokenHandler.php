<?php

declare(strict_types=1);

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final class AccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(private readonly JWTEncoderInterface $jwtEncoder)
    {
    }

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        try {
            $payload = $this->jwtEncoder->decode($accessToken);
        } catch (JWTDecodeFailureException) {
            throw new BadCredentialsException('Invalid or expired token.');
        }

        if (!isset($payload['username']) || !\is_string($payload['username'])) {
            throw new BadCredentialsException('Invalid token payload.');
        }

        return new UserBadge($payload['username']);
    }
}
