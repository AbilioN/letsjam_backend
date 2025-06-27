<?php

namespace Tests\Feature\Chat;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateChatTest extends TestCase
{
    use RefreshDatabase;

    protected $user1;
    protected $user2;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        // Cria usuários e admin
        $this->user1 = User::factory()->create();
        $this->user2 = User::factory()->create();
        $this->admin = Admin::factory()->create();
    }

    public function test_user_can_create_private_chat_with_admin()
    {
        $token = $this->user1->createToken('test')->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/chat/create-private', [
                'other_user_id' => $this->admin->id,
                'other_user_type' => 'admin'
            ]);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['chat' => ['id', 'type', 'name', 'description']]
            ]);
    }

    public function test_admin_can_create_private_chat_with_user()
    {
        $token = $this->admin->createToken('test')->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/chat/create-private', [
                'other_user_id' => $this->user2->id,
                'other_user_type' => 'user'
            ]);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['chat' => ['id', 'type', 'name', 'description']]
            ]);
    }

    public function test_user_can_create_group_chat_with_admin_and_user()
    {
        $token = $this->user1->createToken('test')->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/chat/create-group', [
                'name' => 'Grupo Teste',
                'description' => 'Grupo de teste',
                'participants' => [
                    ['user_id' => $this->admin->id, 'user_type' => 'admin'],
                    ['user_id' => $this->user2->id, 'user_type' => 'user']
                ]
            ]);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['chat' => ['id', 'type', 'name', 'description', 'participants']]
            ]);
        $this->assertCount(3, $response->json('data.chat.participants'));
    }

    public function test_create_group_chat_requires_participants()
    {
        $token = $this->user1->createToken('test')->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/chat/create-group', [
                'name' => 'Grupo Teste',
                'description' => 'Grupo de teste',
                'participants' => []
            ]);
        $response->assertStatus(422);
    }

    public function test_create_private_chat_requires_other_user()
    {
        $token = $this->user1->createToken('test')->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/chat/create-private', []);
        $response->assertStatus(422);
    }
} 