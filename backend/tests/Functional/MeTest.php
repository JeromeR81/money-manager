<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class MeTest extends WebTestCase
{
    private const USER_EMAIL = 'test-me@money-manager.local';
    private const PASSWORD = 'test-password';

    protected function setUp(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $existing = $em->getRepository(User::class)->findOneBy(['email' => self::USER_EMAIL]);
        if (null !== $existing) {
            $em->remove($existing);
            $em->flush();
        }

        $user = new User();
        $user->setEmail(self::USER_EMAIL);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($hasher->hashPassword($user, self::PASSWORD));
        $em->persist($user);
        $em->flush();

        /** @var RateLimiterFactory $limiterFactory */
        $limiterFactory = $container->get('limiter.login_throttle');
        $limiterFactory->create('127.0.0.1')->reset();

        static::ensureKernelShutdown();
    }

    public function testMeWithoutCookie(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/auth/me');

        self::assertResponseStatusCodeSame(401);
    }

    public function testMeWithValidCookie(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            'email' => self::USER_EMAIL,
            'password' => self::PASSWORD,
        ]));
        self::assertResponseStatusCodeSame(200);

        $bearerValue = null;
        foreach ($client->getResponse()->headers->getCookies() as $cookie) {
            if ('BEARER' === $cookie->getName()) {
                $bearerValue = $cookie->getValue();
                break;
            }
        }
        self::assertNotNull($bearerValue, 'Cookie BEARER absent de la réponse login');

        $client->getCookieJar()->set(new Cookie('BEARER', (string) $bearerValue));
        $client->request('GET', '/api/auth/me');

        self::assertResponseStatusCodeSame(200);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame(self::USER_EMAIL, $data['email']);
    }
}
