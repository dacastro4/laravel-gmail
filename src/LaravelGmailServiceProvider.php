<?php

namespace Dacastro4\LaravelGmail;

use Illuminate\Support\ServiceProvider;

class LaravelGmailServiceProvider extends ServiceProvider
{

	public function boot()
	{
		$this->publishes([
         __DIR__ . '/src/config/gmail.php' => config_path( 'gmail.php' ),
        ] );

		$this->loadMigrationsFrom(__DIR__.'/src/migrations');
	}

	public function register(  )
	{
		$this->mergeConfigFrom(
			__DIR__ . '/config/gmail.php', 'gmail'
		);

		// Main Service
		$this->app->bind('laravelgmail', function ($app) {
			return new LaravelGmailClass();
		});

	}
}