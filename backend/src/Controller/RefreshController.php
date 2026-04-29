<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth', stateless: true)]
final class RefreshController extends AbstractController
{
    private const BEARER_TTL = 900;
    private const REFRESH_TTL = 604800;

    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
        private readonly RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route('/refresh', name: 'api_auth_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
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

        // Rotation : delete old token, issue new one
        $this->refreshTokenManager->delete($refreshToken);
        $newRefreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, self::REFRESH_TTL);
        $this->refreshTokenManager->save($newRefreshToken);

        $jwt = $this->jwtManager->create($user);

        $bearerCookie = Cookie::create('BEARER')
            ->withValue($jwt)
            ->withExpires(time() + self::BEARER_TTL)
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_STRICT);

        $refreshCookie = Cookie::create('REFRESH_TOKEN')
            ->withValue((string) $newRefreshToken->getRefreshToken())
            ->withExpires(time() + self::REFRESH_TTL)
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_STRICT);

        $response = $this->json(['message' => 'Token refreshed']);
        $response->headers->setCookie($bearerCookie);
        $response->headers->setCookie($refreshCookie);

        return $response;
    }
}
