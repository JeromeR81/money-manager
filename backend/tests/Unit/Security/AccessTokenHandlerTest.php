<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\AccessTokenHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

final class AccessTokenHandlerTest extends TestCase
{
    public function testValidTokenReturnsBadgeWithUsername(): void
    {
        $encoder = $this->createStub(JWTEncoderInterface::class);
        $encoder->method('decode')->willReturn(['username' => 'user@example.com']);

        $handler = new AccessTokenHandler($encoder);
        $badge = $handler->getUserBadgeFrom('valid.token.here');

        self::assertSame('user@example.com', $badge->getUserIdentifier());
    }

    public function testDecodeFailureThrowsBadCredentials(): void
    {
        $encoder = $this->createStub(JWTEncoderInterface::class);
        $encoder->method('decode')->willThrowException(
            new JWTDecodeFailureException(JWTDecodeFailureException::EXPIRED_TOKEN, 'Expired token.')
        );

        $handler = new AccessTokenHandler($encoder);

        $this->expectException(BadCredentialsException::class);
        $handler->getUserBadgeFrom('expired.token.here');
    }

    public function testMissingUsernameInPayloadThrowsBadCredentials(): void
    {
        $encoder = $this->createStub(JWTEncoderInterface::class);
        $encoder->method('decode')->willReturn(['sub' => 'something', 'iat' => 1234567890]);

        $handler = new AccessTokenHandler($encoder);

        $this->expectException(BadCredentialsException::class);
        $handler->getUserBadgeFrom('token.without.username');
    }

    public function testNonStringUsernameInPayloadThrowsBadCredentials(): void
    {
        $encoder = $this->createStub(JWTEncoderInterface::class);
        $encoder->method('decode')->willReturn(['username' => 42]);

        $handler = new AccessTokenHandler($encoder);

        $this->expectException(BadCredentialsException::class);
        $handler->getUserBadgeFrom('token.with.int.username');
    }
}
