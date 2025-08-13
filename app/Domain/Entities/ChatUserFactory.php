<?php

namespace App\Domain\Entities;

use App\Models\Admin as AdminModel;
use App\Models\User as UserModel;

class ChatUserFactory
{
    /**
     * Cria uma entidade de domínio User a partir do modelo Eloquent
     */
    public static function createUserFromModel(UserModel $model): User
    {
        return new User(
            id: $model->id,
            name: $model->name,
            email: $model->email,
            password: $model->password,
            emailVerifiedAt: $model->email_verified_at
        );
    }

    /**
     * Cria uma entidade de domínio Admin a partir do modelo Eloquent
     */
    public static function createAdminFromModel(AdminModel $model): Admin
    {
        return new Admin(
            id: $model->id,
            name: $model->name,
            email: $model->email,
            password: $model->password,
            isActive: $model->is_active,
            lastLoginAt: $model->last_login_at
        );
    }

    /**
     * Cria uma entidade de domínio a partir do modelo Eloquent
     * Retorna a entidade apropriada baseada no tipo do modelo
     */
    public static function createFromModel(UserModel|AdminModel $model): ChatUser
    {
        if ($model instanceof UserModel) {
            return self::createUserFromModel($model);
        }

        if ($model instanceof AdminModel) {
            return self::createAdminFromModel($model);
        }

        throw new \InvalidArgumentException(
            'Modelo não suportado. Apenas User e Admin são suportados.'
        );
    }

    /**
     * Cria uma entidade de domínio a partir de dados da tabela chat_user
     */
    public static function createFromChatUserData(int $userId, string $userType): ChatUser
    {
        switch ($userType) {
            case 'user':
                $model = UserModel::findOrFail($userId);
                return self::createUserFromModel($model);

            case 'admin':
                $model = AdminModel::findOrFail($userId);
                return self::createAdminFromModel($model);

            default:
                throw new \InvalidArgumentException(
                    "Tipo '{$userType}' não suportado. Use 'user' ou 'admin'."
                );
        }


    }
} 