<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use App\Security\Cookie\AuthCookieFactory;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth', stateless: true)]
final class RefreshController extends AbstractController
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
        private readonly RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private readonly UserRepository $userRepository,
        private readonly AuthCookieFactory $cookieFactory,
        private readonly RateLimiterFactory $refreshThrottleLimiter,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/refresh', name: 'api_auth_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $limiter = $this->refreshThrottleLimiter->create((string) $request->getClientIp());
        if (!$limiter->consume()->isAccepted()) {
            return $this->json(['message' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $refreshTokenString = $request->cookies->get('REFRESH_TOKEN');

        if (null === $refreshTokenString) {
            return $this->json(['message' => 'Invalid refresh token'], Response::HTTP_UNAUTHORIZED);
        }

        $refreshToken = $this->refreshTokenManager->get($refreshTokenString);

        if (null === $refreshToken || !$refreshToken->isValid()) {
            return $this->json(['message' => 'Invalid refresh token'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $this->userRepository->findOneBy(['email' => $refreshToken->getUsername()]);

        if (null === $user) {
            return $this->json(['message' => 'Invalid refresh token'], Response::HTTP_UNAUTHORIZED);
        }

        $newRefreshTokenString = $this->em->wrapInTransaction(function () use ($refreshToken, $user): string {
            $this->refreshTokenManager->delete($refreshToken);
            $newRefreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, AuthCookieFactory::REFRESH_TTL);
            $this->refreshTokenManager->save($newRefreshToken);

            return (string) $newRefreshToken->getRefreshToken();
        });

        $response = $this->json(['message' => 'Token refreshed']);
        $response->headers->setCookie($this->cookieFactory->createBearer($this->jwtManager->create($user)));
        $response->headers->setCookie($this->cookieFactory->createRefreshToken($newRefreshTokenString));

        return $response;
    }
}
