<?php

use Dacastro4\LaravelGmail\Repositories\StorageTokenRepository;
use Dacastro4\LaravelGmail\Services\Message\Mail;
use Illuminate\Container\Container;
use Illuminate\Mail\Markdown;
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
}
