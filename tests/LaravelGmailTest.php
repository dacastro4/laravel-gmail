<?php

use Dacastro4\LaravelGmail\Facade\LaravelGmail;
use Dacastro4\LaravelGmail\LaravelGmailClass;
use Tests\TestCase;

class LaravelGmailTest extends TestCase
{

	/**
	 * @var LaravelGmailClass
	 */
	public $laravel;

	protected function getEnvironmentSetUp( $app )
	{
		$this->laravel = new LaravelGmailClass($app['config']);
	}

	/**
	 * @test
	 */
	public function returns_gmail_authentication_url()
	{
		$url = $this->laravel->getAuthUrl();

	}

}
