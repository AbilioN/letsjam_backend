<?php

namespace App\Domain\Entities;

use DateTime;

class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly ?DateTime $emailVerifiedAt = null
    ) {}

    public function validatePassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    public function generateToken(): string
    {
        // Lógica simples de geração de token para o Domain
        return md5($this->email . time());
    }

    public function isEmailVerified(): bool
    {
        return !is_null($this->emailVerifiedAt);
    }
}