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
            password: $userModel->password
        );
    }

    public function save(User $user): User
    {
        $userModel = UserModel::updateOrCreate(
            ['email' => $user->email],
            [
                'name' => $user->name,
                'password' => $user->password
            ]
        );

        return new User(
            id: $userModel->id,
            name: $userModel->name,
            email: $userModel->email,
            password: $userModel->password
        );
    }
}
