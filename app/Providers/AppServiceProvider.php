<?php

namespace App\Providers;

use App\Contracts\AccountServiceInterface;
use App\Contracts\TransactionServiceInterface;
use App\Contracts\UserServiceInterface;
use App\Services\AccountService;
use App\Services\TransactionService;
use App\Services\UserService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(AccountServiceInterface::class, AccountService::class);
        $this->app->bind(TransactionServiceInterface::class, TransactionService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
