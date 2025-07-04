<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Infrastructure\Repositories\EloquentUserRepository;
use App\Domain\Services\AuthServiceInterface;
use App\Infrastructure\Services\AuthService;
use App\Domain\Services\RegistrationServiceInterface;
use App\Infrastructure\Services\RegistrationService;
use App\Domain\Services\EmailVerificationServiceInterface;
use App\Infrastructure\Services\EmailVerificationService;
use App\Domain\Repositories\AdminRepositoryInterface;
use App\Infrastructure\Repositories\AdminRepository;
use App\Domain\Services\AdminAuthServiceInterface;
use App\Infrastructure\Services\AdminAuthService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // User dependencies
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(RegistrationServiceInterface::class, RegistrationService::class);
        $this->app->bind(EmailVerificationServiceInterface::class, EmailVerificationService::class);

        // Admin dependencies
        $this->app->bind(AdminRepositoryInterface::class, AdminRepository::class);
        $this->app->bind(AdminAuthServiceInterface::class, AdminAuthService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
