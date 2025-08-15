<?php


use Dacastro4\LaravelGmail\Repositories\StorageTokenRepository;
use Dacastro4\LaravelGmail\LaravelGmailClass;
use Dacastro4\LaravelGmail\Services\Message\Mail;
use Illuminate\Container\Container;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LaravelGmailTest extends TestCase
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
        ]);
    }

    /** @test */
    public function test_markdown_method()
    {
        // mocks
        $mocked_markdown = Mockery::mock(Markdown::class);
        Container::getInstance()->instance(Markdown::class, $mocked_markdown);

        // expectations
        $mocked_markdown->shouldReceive('theme')->once()->with(config('mail.markdown.theme'));
        $mocked_markdown->shouldReceive('render')->once()->with(
            'sample-markdown',
            ['url' => 'https://www.google.com']
        );

        // trigger
        $repository = new StorageTokenRepository;
        (new Mail($repository))->markdown(
            'sample-markdown',
            ['url' => 'https://www.google.com']
        );
    }

    /** @test */
    public function test_format_email_list_parses_names_and_addresses()
    {
        $emails = 'Alice <alice@example.com>, Bob <bob@example.com>';

        $repository = new StorageTokenRepository;
        $formatted = (new Mail($repository))->formatEmailList($emails);

        $this->assertCount(2, $formatted);
        $this->assertEquals(['name' => 'Alice', 'email' => 'alice@example.com'], $formatted[0]);
        $this->assertEquals(['name' => 'Bob', 'email' => 'bob@example.com'], $formatted[1]);
    }

    /** @test */
    public function test_user_returns_email_from_token_file()
    {
        Storage::fake('local');

        config([
            'gmail.client_secret' => 'secret',
            'gmail.client_id' => 'client',
            'gmail.redirect_url' => '/',
            'gmail.credentials_file_name' => 'test-token',
            'gmail.allow_json_encrypt' => false,
            'gmail.allow_multiple_credentials' => false,
        ]);

        Storage::disk('local')->put(
            'gmail/tokens/test-token.json',
            json_encode([
                'access_token' => 'token',
                'refresh_token' => 'refresh',
                'email' => 'foo@example.com',
                'expires_in' => 3600,
                'created' => time(),
            ])
        );

        $client = new LaravelGmailClass($this->app['config']);

        $this->assertEquals('foo@example.com', $client->user());
    }

    /** @test */
    public function test_set_user_id_updates_property()
    {
        Storage::fake('local');

        config([
            'gmail.client_secret' => 'secret',
            'gmail.client_id' => 'client',
            'gmail.redirect_url' => '/',
            'gmail.credentials_file_name' => 'test-token',
            'gmail.allow_json_encrypt' => false,
            'gmail.allow_multiple_credentials' => false,
        ]);

        $client = new LaravelGmailClass($this->app['config']);
        $client->setUserId('user-123');

        $this->assertEquals('user-123', $client->getUserId());
    }
}
