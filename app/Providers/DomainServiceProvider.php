<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Infrastructure\Repositories\EloquentUserRepository;
use App\Domain\Services\AuthServiceInterface;
use App\Infrastructure\Services\AuthService;
use App\Domain\Services\RegistrationServiceInterface;
use App\Infrastructure\Services\RegistrationService;

class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repositories
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        
        // Services
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(RegistrationServiceInterface::class, RegistrationService::class);
    }

    public function boot(): void
    {
        //
    }
}
