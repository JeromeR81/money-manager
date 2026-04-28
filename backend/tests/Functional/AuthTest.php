<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class AuthTest extends WebTestCase
{
    private const USER_EMAIL = 'test-user@money-manager.local';
    private const ADMIN_EMAIL = 'test-admin@money-manager.local';
    private const PASSWORD = 'test-password';

    protected function setUp(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);

        foreach ([self::USER_EMAIL, self::ADMIN_EMAIL] as $email) {
            $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if (null !== $existing) {
                $em->remove($existing);
            }
        }
        $em->flush();

        $user = new User();
        $user->setEmail(self::USER_EMAIL);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($hasher->hashPassword($user, self::PASSWORD));
        $em->persist($user);

        $admin = new User();
        $admin->setEmail(self::ADMIN_EMAIL);
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($hasher->hashPassword($admin, self::PASSWORD));
        $em->persist($admin);

        $em->flush();

        /** @var RateLimiterFactory $limiterFactory */
        $limiterFactory = $container->get('limiter.login_throttle');
        $limiterFactory->create('127.0.0.1')->reset();
        $limiterFactory->create('10.0.0.99')->reset();

        static::ensureKernelShutdown();
    }

    public function testLoginValidUserRole(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            'email' => self::USER_EMAIL,
            'password' => self::PASSWORD,
        ]));

        self::assertResponseStatusCodeSame(200);

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('Authenticated', $data['message']);

        $bearerCookie = $this->getBearerCookie($client);
        self::assertNotNull($bearerCookie, 'Cookie BEARER absent de la réponse');
        self::assertTrue($bearerCookie->isHttpOnly(), 'Cookie BEARER doit être HttpOnly');
        self::assertTrue($bearerCookie->isSecure(), 'Cookie BEARER doit être Secure');
    }

    public function testLoginValidAdminRole(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            'email' => self::ADMIN_EMAIL,
            'password' => self::PASSWORD,
        ]));

        self::assertResponseStatusCodeSame(200);
        self::assertNotNull($this->getBearerCookie($client), 'Cookie BEARER absent de la réponse admin');
    }

    public function testLoginInvalidPassword(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            'email' => self::USER_EMAIL,
            'password' => 'wrong-password',
        ]));

        self::assertResponseStatusCodeSame(401);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('Invalid credentials', $data['message']);
    }

    public function testLoginUnknownEmail(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            'email' => 'nobody@money-manager.local',
            'password' => self::PASSWORD,
        ]));

        self::assertResponseStatusCodeSame(401);
    }

    public function testProtectedEndpointWithoutCookie(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api');

        self::assertResponseStatusCodeSame(401);
    }

    public function testProtectedEndpointWithValidCookie(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            'email' => self::USER_EMAIL,
            'password' => self::PASSWORD,
        ]));

        $bearerCookie = $this->getBearerCookie($client);
        self::assertNotNull($bearerCookie, 'Login doit retourner un cookie BEARER');

        $client->getCookieJar()->set(new Cookie('BEARER', (string) $bearerCookie->getValue()));
        $client->request('GET', '/api');

        self::assertResponseStatusCodeSame(200);
    }

    public function testLogout(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/logout', [], [], ['CONTENT_TYPE' => 'application/json']);

        self::assertResponseStatusCodeSame(200);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('Logged out', $data['message']);

        $bearerCookie = $this->getBearerCookie($client);
        self::assertNotNull($bearerCookie, 'Cookie BEARER doit être présent dans la réponse logout');
        self::assertLessThanOrEqual(1, $bearerCookie->getExpiresTime(), 'Cookie BEARER doit être expiré');
    }

    public function testLoginInvalidJson(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'], 'not-valid-json');

        self::assertResponseStatusCodeSame(401);
    }

    public function testLoginRateLimiting(): void
    {
        $client = static::createClient([], ['REMOTE_ADDR' => '10.0.0.99']);

        for ($i = 0; $i < 5; ++$i) {
            $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
                'email' => 'nobody@money-manager.local',
                'password' => 'wrong',
            ]));
            self::assertResponseStatusCodeSame(401);
        }

        $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            'email' => 'nobody@money-manager.local',
            'password' => 'wrong',
        ]));
        self::assertResponseStatusCodeSame(429);
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
}
