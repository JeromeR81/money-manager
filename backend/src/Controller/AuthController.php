<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\LoginRequest;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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
    ) {
    }

    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $loginRequest = new LoginRequest(
            email: $data['email'] ?? '',
            password: $data['password'] ?? '',
        );

        $violations = $this->validator->validate($loginRequest);
        if (\count($violations) > 0) {
            return $this->json(['message' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $this->userRepository->findOneBy(['email' => $loginRequest->email]);
        if (null === $user || !$this->passwordHasher->isPasswordValid($user, $loginRequest->password)) {
            return $this->json(['message' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $this->jwtManager->create($user);

        $cookie = Cookie::create('BEARER')
            ->withValue($token)
            ->withExpires(time() + 900)
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_STRICT);

        $response = $this->json(['message' => 'Authenticated']);
        $response->headers->setCookie($cookie);

        return $response;
    }

    #[Route('/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        $cookie = Cookie::create('BEARER')
            ->withValue('')
            ->withExpires(1)
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_STRICT);

        $response = $this->json(['message' => 'Logged out']);
        $response->headers->setCookie($cookie);

        return $response;
    }
}
