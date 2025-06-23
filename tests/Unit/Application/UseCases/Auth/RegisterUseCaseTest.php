<?php

namespace Tests\Unit\Application\UseCases\Auth;

use App\Application\UseCases\Auth\RegisterUseCase;
use App\Domain\Entities\User;
use App\Domain\Exceptions\RegistrationException;
use App\Domain\Services\RegistrationServiceInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class RegisterUseCaseTest extends TestCase
{
    private RegisterUseCase $registerUseCase;
    private RegistrationServiceInterface $registrationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registrationService = Mockery::mock(RegistrationServiceInterface::class);
        $this->registerUseCase = new RegisterUseCase($this->registrationService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_execute_returns_user_data_and_token_with_valid_data()
    {
        // Arrange
        $user = new User(
            id: 1,
            name: 'John Doe',
            email: 'john@example.com',
            password: 'hashed_password'
        );

        $this->registrationService
            ->shouldReceive('register')
            ->with('John Doe', 'john@example.com', 'password123')
            ->once()
            ->andReturn($user);

        $this->registrationService
            ->shouldReceive('generateToken')
            ->with($user)
            ->once()
            ->andReturn('valid-token-123');

        // Act
        $result = $this->registerUseCase->execute('John Doe', 'john@example.com', 'password123');

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        
        $this->assertEquals([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ], $result['user']);
        
        $this->assertEquals('valid-token-123', $result['token']);
    }

    public function test_execute_throws_exception_when_registration_fails()
    {
        // Arrange
        $this->registrationService
            ->shouldReceive('register')
            ->with('John Doe', 'existing@example.com', 'password123')
            ->once()
            ->andThrow(new RegistrationException('Email already exists'));

        // Act & Assert
        $this->expectException(RegistrationException::class);
        $this->registerUseCase->execute('John Doe', 'existing@example.com', 'password123');
    }
} 