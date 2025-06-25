<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\User;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Models\User as UserModel;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findByEmail(string $email): ?User
    {
        $userModel = UserModel::where('email', $email)->first();
        
        if (!$userModel) {
            return null;
        }

        return new User(
            id: $userModel->id,
            name: $userModel->name,
            email: $userModel->email,
            password: $userModel->password,
            emailVerifiedAt: $userModel->email_verified_at
        );
    }

    public function save(User $user): User
    {
        $userModel = UserModel::updateOrCreate(
            ['email' => $user->email],
            [
                'name' => $user->name,
                'password' => $user->password,
                'email_verified_at' => $user->emailVerifiedAt
            ]
        );

        return new User(
            id: $userModel->id,
            name: $userModel->name,
            email: $userModel->email,
            password: $userModel->password,
            emailVerifiedAt: $userModel->email_verified_at
        );
    }
}
