<?php

namespace App\UseCases\Auth;

use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\AuthenticationException;

class LoginUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function execute(string $email, string $password): string
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !Hash::check($password, $user->password)) {
            throw new AuthenticationException('Invalid credentials');
        }

        // Gera token (exemplo usando Sanctum)
        return $user->createToken('api')->plainTextToken;
    }
}