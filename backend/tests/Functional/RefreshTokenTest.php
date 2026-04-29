<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RefreshTokenTest extends WebTestCase
{
    private const USER_EMAIL = 'test-refresh@money-manager.local';
    private const PASSWORD = 'test-password';

    protected function setUp(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);

        // Purge refresh tokens for the test user
        foreach ($em->getRepository(RefreshToken::class)->findAll() as $rt) {
            if (self::USER_EMAIL === $rt->getUsername()) {
                $em->remove($rt);
            }
        }

        // Purge test user
        $existing = $em->getRepository(User::class)->findOneBy(['email' => self::USER_EMAIL]);
        if (null !== $existing) {
            $em->remove($existing);
        }
        $em->flush();

        $user = new User();
        $user->setEmail(self::USER_EMAIL);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($hasher->hashPassword($user, self::PASSWORD));
        $em->persist($user);
        $em->flush();

        static::ensureKernelShutdown();
    }

    public function testRefreshWithValidToken(): void
    {
        $client = static::createClient();

        // Login to get a refresh token
        $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            'email' => self::USER_EMAIL,
            'password' => self::PASSWORD,
        ]));
        self::assertResponseStatusCodeSame(200);

        $refreshCookie = $this->getRefreshTokenCookie($client);
        self::assertNotNull($refreshCookie, 'Cookie REFRESH_TOKEN absent après login');

        // Use the refresh token
        $client->getCookieJar()->set(new Cookie('REFRESH_TOKEN', (string) $refreshCookie->getValue()));
        $client->request('POST', '/api/auth/refresh');

        self::assertResponseStatusCodeSame(200);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('Token refreshed', $data['message']);

        $newBearer = $this->getBearerCookie($client);
        self::assertNotNull($newBearer, 'Cookie BEARER absent après refresh');
        self::assertTrue($newBearer->isHttpOnly());
        self::assertTrue($newBearer->isSecure());

        $newRefresh = $this->getRefreshTokenCookie($client);
        self::assertNotNull($newRefresh, 'Cookie REFRESH_TOKEN absent après refresh');
        self::assertTrue($newRefresh->isHttpOnly());
        self::assertTrue($newRefresh->isSecure());
    }

    public function testRefreshWithoutToken(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/refresh');

        self::assertResponseStatusCodeSame(401);
    }

    public function testRefreshWithInvalidToken(): void
    {
        $client = static::createClient();
        $client->getCookieJar()->set(new Cookie('REFRESH_TOKEN', 'invalid-token-string'));
        $client->request('POST', '/api/auth/refresh');

        self::assertResponseStatusCodeSame(401);
    }

    public function testRefreshAfterLogout(): void
    {
        $client = static::createClient();

        // Login
        $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            'email' => self::USER_EMAIL,
            'password' => self::PASSWORD,
        ]));
        $refreshCookie = $this->getRefreshTokenCookie($client);
        self::assertNotNull($refreshCookie);

        // Logout (revokes token)
        $client->getCookieJar()->set(new Cookie('REFRESH_TOKEN', (string) $refreshCookie->getValue()));
        $client->request('POST', '/api/auth/logout');
        self::assertResponseStatusCodeSame(200);

        // Try to refresh with the revoked token
        $client->getCookieJar()->set(new Cookie('REFRESH_TOKEN', (string) $refreshCookie->getValue()));
        $client->request('POST', '/api/auth/refresh');

        self::assertResponseStatusCodeSame(401);
    }

    public function testDoubleRefreshRevokesPreviousToken(): void
    {
        $client = static::createClient();

        // Login
        $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            'email' => self::USER_EMAIL,
            'password' => self::PASSWORD,
        ]));
        $originalRefresh = $this->getRefreshTokenCookie($client);
        self::assertNotNull($originalRefresh);

        // First refresh — consumes original token
        $client->getCookieJar()->set(new Cookie('REFRESH_TOKEN', (string) $originalRefresh->getValue()));
        $client->request('POST', '/api/auth/refresh');
        self::assertResponseStatusCodeSame(200);

        // Second refresh with the original (now revoked) token
        $client->getCookieJar()->set(new Cookie('REFRESH_TOKEN', (string) $originalRefresh->getValue()));
        $client->request('POST', '/api/auth/refresh');

        self::assertResponseStatusCodeSame(401);
    }

    private function getBearerCookie(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): ?\Symfony\Component\HttpFoundation\Cookie
    {
        foreach ($client->getResponse()->headers->getCookies() as $cookie) {
            if ('BEARER' === $cookie->getName()) {
                return $cookie;
            }
        }

        return null;
    }

    private function getRefreshTokenCookie(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): ?\Symfony\Component\HttpFoundation\Cookie
    {
        foreach ($client->getResponse()->headers->getCookies() as $cookie) {
            if ('REFRESH_TOKEN' === $cookie->getName()) {
                return $cookie;
            }
        }

        return null;
    }
}
