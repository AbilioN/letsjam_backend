<?php

namespace App\Application\UseCases\Chat;

use App\Domain\Repositories\ChatRepositoryInterface;

class CreateGroupChatUseCase
{
    public function __construct(private ChatRepositoryInterface $chatRepository) {}

    /**
     * @param int $creatorId
     * @param string $creatorType
     * @param string $name
     * @param string|null $description
     * @param array $participants // [ [user_id, user_type], ... ]
     * @return array
     */
    public function execute(int $creatorId, string $creatorType, string $name, ?string $description, array $participants): array
    {
        // Garante que o criador está na lista de participantes
        $participants[] = ['user_id' => $creatorId, 'user_type' => $creatorType];
        $chat = $this->chatRepository->createGroupChat($name, $description ?? '', $creatorId, $creatorType);
        // Adiciona participantes
        foreach ($participants as $p) {
            $this->chatRepository->addParticipantToChat($chat->id, $p['user_id'], $p['user_type']);
        }
        return [
            'chat' => [
                'id' => $chat->id,
                'type' => $chat->type,
                'name' => $chat->name,
                'description' => $chat->description,
                'participants' => $participants,
            ]
        ];
    }
} 