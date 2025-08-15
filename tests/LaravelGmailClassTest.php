<?php

namespace Tests;

use Dacastro4\LaravelGmail\LaravelGmailClass;
use Illuminate\Support\Facades\Storage;
use Mockery;

class LaravelGmailClassTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'gmail.client_secret' => 'secret',
            'gmail.client_id' => 'client',
            'gmail.redirect_url' => '/',
            'gmail.credentials_file_name' => 'test-token',
            'gmail.allow_json_encrypt' => false,
            'gmail.allow_multiple_credentials' => false,
        ]);
    }

    /** @test */
    public function user_method_returns_email_from_saved_token()
    {
        Storage::fake('local');
        $client = new LaravelGmailClass($this->app['config']);

        $ref = new \ReflectionProperty(LaravelGmailClass::class, 'emailAddress');
        $ref->setAccessible(true);
        $ref->setValue($client, 'user@example.com');

        $client->saveAccessToken([
            'access_token' => 'token',
            'refresh_token' => 'refresh',
        ]);

        $this->assertEquals('user@example.com', $client->user());
    }

    /** @test */
    public function set_user_id_updates_property()
    {
        $client = new LaravelGmailClass($this->app['config']);
        $returned = $client->setUserId('abc123');

        $this->assertSame($client, $returned);
        $this->assertEquals('abc123', $client->getUserId());
    }

    /** @test */
    public function redirect_returns_redirect_response_to_auth_url()
    {
        $client = Mockery::mock(LaravelGmailClass::class.'[getAuthUrl]', [$this->app['config']]);
        $client->shouldReceive('getAuthUrl')->once()->andReturn('https://example.com/auth');

        $response = $client->redirect();

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertEquals('https://example.com/auth', $response->getTargetUrl());
    }

    /** @test */
    public function logout_revokes_and_deletes_token()
    {
        $client = Mockery::mock(LaravelGmailClass::class.'[revokeToken,deleteAccessToken]', [$this->app['config']]);
        $client->shouldReceive('revokeToken')->once();
        $client->shouldReceive('deleteAccessToken')->once();

        $client->logout();
    }
}
