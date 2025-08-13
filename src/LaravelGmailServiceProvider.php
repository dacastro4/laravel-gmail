<?php

declare(strict_types=1);

namespace Dacastro4\LaravelGmail;

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

        // Main Service
        $this->app->bind('laravelgmail', function ($app): LaravelGmailClass {
            return new LaravelGmailClass($app['config']);
        });
    }
}
