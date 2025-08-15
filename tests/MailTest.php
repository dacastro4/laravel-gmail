<?php

namespace Tests;

use Dacastro4\LaravelGmail\Services\Message\Mail;
use Illuminate\Support\Facades\Storage;

class MailTest extends TestCase
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
    public function get_from_parses_name_and_email()
    {
        Storage::fake('local');
        $mail = new Mail;

        $header = new \Google_Service_Gmail_MessagePartHeader;
        $header->setName('From');
        $header->setValue('John Doe <john@example.com>');
        $payload = new \Google_Service_Gmail_MessagePart;
        $payload->setHeaders([$header]);

        $ref = new \ReflectionProperty(Mail::class, 'payload');
        $ref->setAccessible(true);
        $ref->setValue($mail, $payload);

        $from = $mail->getFrom();

        $this->assertSame('John Doe', $from['name']);
        $this->assertSame('john@example.com', $from['email']);
        $this->assertSame('john@example.com', $mail->getFromEmail());
    }
}
