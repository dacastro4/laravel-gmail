<?php

use Dacastro4\LaravelGmail\GmailConnection;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TokenRepositoryTest extends TestCase
{
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        config(['app.key' => 'base64:'.base64_encode('testing_app_key_1234567890123456')]);

        $this->config = [
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
            'gmail.allow_json_encrypt' => true,
        ];
    }

    /** @test */
    public function has_token_with_encryption(): void
    {
        $repository = new GmailConnection($this->config);

        $this->assertFalse($repository->checkPreviouslyLoggedIn());

        $repository->saveAccessToken([
            'access_token' => 'token',
            'refresh_token' => 'refresh',
        ]);

        $this->assertTrue($repository->checkPreviouslyLoggedIn());
    }

    /** @test */
    public function save_stores_encrypted_token(): void
    {
        $repository = new GmailConnection($this->config);

        $token = [
            'access_token' => 'token',
            'refresh_token' => 'refresh',
        ];

        $repository->saveAccessToken($token);

        $path = 'gmail/tokens/gmail-json.json';
        $contents = Storage::disk('local')->get($path);

        $this->assertNotSame(json_encode($token + ['email' => null]), $contents);
        $this->assertSame($token + ['email' => null], json_decode(decrypt($contents), true));
    }

    /** @test */
    public function delete_removes_encrypted_token(): void
    {
        $repository = new GmailConnection($this->config);

        $repository->saveAccessToken([
            'access_token' => 'token',
            'refresh_token' => 'refresh',
        ]);
        $this->assertTrue($repository->checkPreviouslyLoggedIn());

        $repository->deleteAccessToken();

        $this->assertFalse($repository->checkPreviouslyLoggedIn());

        $path = 'gmail/tokens/gmail-json.json';
        $this->assertSame([], json_decode(decrypt(Storage::disk('local')->get($path)), true));
    }
}
