<?php

use Dacastro4\LaravelGmail\GmailConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MakeTokenTest extends TestCase
{
    /** @test */
    public function make_token_uses_injected_request_code()
    {
        Storage::fake('local');

        $config = [
            'gmail.client_secret' => 'secret',
            'gmail.client_id' => 'id',
            'gmail.redirect_url' => '/callback',
            'gmail.state' => null,
            'gmail.scopes' => [],
            'gmail.additional_scopes' => [],
            'gmail.access_type' => 'offline',
            'gmail.approval_prompt' => 'force',
            'gmail.credentials_file_name' => 'gmail-json',
            'gmail.allow_multiple_credentials' => false,
            'gmail.allow_json_encrypt' => false,
        ];

        $connection = \Mockery::mock(GmailConnection::class.'[check,fetchAccessTokenWithAuthCode]', [$config]);
        $connection->shouldReceive('check')->andReturn(false);
        $connection->shouldReceive('fetchAccessTokenWithAuthCode')
            ->with('test-code')
            ->once()
            ->andReturn(['access_token' => 'token']);

        $request = Request::create('/callback', 'GET', ['code' => 'test-code']);

        $this->assertEquals(
            ['access_token' => 'token'],
            $connection->makeToken($request)
        );
    }
}
