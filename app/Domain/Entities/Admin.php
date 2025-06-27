<?php

namespace App\Domain\Entities;

use DateTime;

class Admin
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly bool $isActive,
        public readonly ?DateTime $lastLoginAt = null
    ) {}

    public function validatePassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    public function generateToken(): string
    {
        // Lógica simples de geração de token para o Domain
        return md5($this->email . time() . 'admin');
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
} 