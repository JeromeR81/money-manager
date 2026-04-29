<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\LoginRequest;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\Cookie\AuthCookieFactory;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth', stateless: true)]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly ValidatorInterface $validator,
        private readonly RateLimiterFactory $loginThrottleLimiter,
        private readonly RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
        private readonly AuthCookieFactory $cookieFactory,
    ) {
    }

    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $limiter = $this->loginThrottleLimiter->create((string) $request->getClientIp());
        if (!$limiter->consume()->isAccepted()) {
            return $this->json(['message' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        try {
            $data = $request->toArray();
        } catch (\Exception) {
            return $this->json(['message' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        $loginRequest = new LoginRequest(
            email: $data['email'] ?? '',
            password: $data['password'] ?? '',
        );

        $violations = $this->validator->validate($loginRequest);
        if (\count($violations) > 0) {
            return $this->json(['message' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $this->userRepository->findOneBy(['email' => $loginRequest->email]);

        // Always hash to prevent timing attacks — even when the user doesn't exist.
        $dummyUser = new User();
        $dummyUser->setPassword('$2y$13$invalidhashusedastimingguard00000000000000000000000000000');
        $validPassword = $this->passwordHasher->isPasswordValid(
            null !== $user ? $user : $dummyUser,
            $loginRequest->password,
        );

        if (null === $user || !$validPassword) {
            return $this->json(['message' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        $limiter->reset();

        $token = $this->jwtManager->create($user);

        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, AuthCookieFactory::REFRESH_TTL);
        $this->refreshTokenManager->save($refreshToken);

        $response = $this->json(['message' => 'Authenticated']);
        $response->headers->setCookie($this->cookieFactory->createBearer($token));
        $response->headers->setCookie($this->cookieFactory->createRefreshToken((string) $refreshToken->getRefreshToken()));

        return $response;
    }

    #[Route('/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $refreshTokenString = $request->cookies->get('REFRESH_TOKEN');
        if (null !== $refreshTokenString) {
            $refreshToken = $this->refreshTokenManager->get($refreshTokenString);
            if (null !== $refreshToken) {
                $this->refreshTokenManager->delete($refreshToken);
            }
        }

        $response = $this->json(['message' => 'Logged out']);
        $response->headers->setCookie($this->cookieFactory->clearBearer());
        $response->headers->setCookie($this->cookieFactory->clearRefreshToken());

        return $response;
    }
}
