<?php

namespace App\Application\UseCases\Auth;

use App\Domain\Services\EmailVerificationServiceInterface;

class ResendVerificationCodeUseCase
{
    public function __construct(
        private EmailVerificationServiceInterface $emailVerificationService
    ) {}

    public function execute(string $email): array
    {
        $this->emailVerificationService->resendVerificationCode($email);

        return [
            'message' => 'Verification code sent successfully',
            'email' => $email
        ];
    }
} 