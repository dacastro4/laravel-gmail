<?php

declare(strict_types=1);

namespace Dacastro4\LaravelGmail;

use Dacastro4\LaravelGmail\Contracts\TokenRepository;
use Dacastro4\LaravelGmail\Repositories\StorageTokenRepository;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class LaravelGmailServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([__DIR__.'/config/gmail.php' => App::make('path.config').'/gmail.php']);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/gmail.php', 'gmail');

        $this->app->bind(TokenRepository::class, fn (): TokenRepository => new StorageTokenRepository);

        // Main Service
        $this->app->bind('laravelgmail', function ($app): LaravelGmailClass {
            return new LaravelGmailClass($app['config'], $app->make(TokenRepository::class));
        });
    }
}
