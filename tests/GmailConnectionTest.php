<?php

namespace Tests;

use Dacastro4\LaravelGmail\GmailConnection;
use Illuminate\Support\Facades\Storage;

class GmailConnectionTest extends TestCase
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
    public function set_both_access_token_stores_and_sets_token()
    {
        Storage::fake('local');
        $connection = new GmailConnection($this->app['config']);

        $token = ['access_token' => 'token', 'refresh_token' => 'refresh'];
        $connection->setBothAccessToken($token);

        Storage::disk('local')->assertExists('gmail/tokens/test-token.json');
        $this->assertSame($token, $connection->getAccessToken());
    }

    /** @test */
    public function get_user_id_returns_value_passed_to_constructor()
    {
        $connection = new GmailConnection($this->app['config'], 'user-123');

        $this->assertEquals('user-123', $connection->getUserId());
    }
}
