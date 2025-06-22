<?php

namespace App\Infrastructure\Services;

use App\Domain\Entities\User;
use App\Domain\Services\AuthServiceInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Exceptions\AuthenticationException;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function authenticate(string $email, string $password): ?User
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !$user->validatePassword($password)) {
            throw new AuthenticationException();
        }

        return $user;
    }

    public function generateToken(User $user): string
    {
        // Implementação usando Sanctum, JWT, etc.
        return $user->createToken('api')->plainTextToken;
    }
}
