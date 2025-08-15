<?php

use Dacastro4\LaravelGmail\Exceptions\AuthException;
use Dacastro4\LaravelGmail\GmailConnection;
use Dacastro4\LaravelGmail\LaravelGmailClass;
use Dacastro4\LaravelGmail\Repositories\StorageTokenRepository;
use Dacastro4\LaravelGmail\Services\Message;
use Dacastro4\LaravelGmail\Services\Message\Attachment;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GmailServiceTest extends TestCase
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
    public function test_token_generation_saves_token_file()
    {
        Storage::fake('local');

        $repository = new StorageTokenRepository;
        $connection = new GmailConnection($repository, $this->app['config']);
        $token = ['access_token' => 'token', 'refresh_token' => 'refresh'];
        $connection->saveAccessToken($token);

        Storage::disk('local')->assertExists('gmail/tokens/test-token.json');
        $this->assertTrue($connection->checkPreviouslyLoggedIn());
    }

    /** @test */
    public function test_check_previously_logged_in_returns_false_when_no_token()
    {
        Storage::fake('local');

        $repository = new StorageTokenRepository;
        $connection = new GmailConnection($repository, $this->app['config']);

        $this->assertFalse($connection->checkPreviouslyLoggedIn());
    }

    /** @test */
    public function test_message_retrieval_returns_collection()
    {
        Storage::fake('local');
        $repository = new StorageTokenRepository;
        $client = new LaravelGmailClass($this->app['config'], $repository);
        $service = new \stdClass;

        $googleMessage = new \Google_Service_Gmail_Message;
        $googleMessage->setId('msg1');

        $response = Mockery::mock();
        $response->shouldReceive('getMessages')->andReturn([$googleMessage]);
        $response->shouldReceive('getNextPageToken')->andReturn(null);

        $messagesResource = Mockery::mock();
        $messagesResource->shouldReceive('listUsersMessages')->with('me', [])->andReturn($response);

        $service->users_messages = $messagesResource;

        $messageService = new Message($client);
        $messageService->service = $service;

        $result = $messageService->all();

        $this->assertCount(1, $result);
    }

    /** @test */
    public function test_message_retrieval_handles_empty_response()
    {
        Storage::fake('local');
        $repository = new StorageTokenRepository;
        $client = new LaravelGmailClass($this->app['config'], $repository);
        $service = new \stdClass;

        $response = Mockery::mock();
        $response->shouldReceive('getMessages')->andReturn([]);
        $response->shouldReceive('getNextPageToken')->andReturn(null);

        $messagesResource = Mockery::mock();
        $messagesResource->shouldReceive('listUsersMessages')->andReturn($response);

        $service->users_messages = $messagesResource;

        $messageService = new Message($client);
        $messageService->service = $service;

        $result = $messageService->all();

        $this->assertCount(0, $result);
    }

    /** @test */
    public function test_attachment_can_be_saved()
    {
        Storage::fake('local');

        $body = Mockery::mock();
        $body->shouldReceive('getAttachmentId')->andReturn('attach');
        $body->shouldReceive('getSize')->andReturn(10);

        $part = Mockery::mock(\Google_Service_Gmail_MessagePart::class);
        $part->shouldReceive('getBody')->andReturn($body);
        $part->shouldReceive('getFilename')->andReturn('file.txt');
        $part->shouldReceive('getMimeType')->andReturn('text/plain');
        $part->shouldReceive('getHeaders')->andReturn([]);

        $repository = new StorageTokenRepository;
        $attachment = new Attachment($repository, 'msg', $part);

        $attachmentsResource = Mockery::mock();
        $attachmentData = Mockery::mock();
        $attachmentData->shouldReceive('getData')->andReturn(base64_encode('content'));
        $attachmentsResource->shouldReceive('get')->with('me', 'msg', 'attach')->andReturn($attachmentData);

        $service = new \stdClass;
        $service->users_messages_attachments = $attachmentsResource;
        $ref = new ReflectionProperty(Attachment::class, 'service');
        $ref->setAccessible(true);
        $ref->setValue($attachment, $service);

        $path = $attachment->saveAttachmentTo('attachments', 'file.txt', 'local');

        Storage::disk('local')->assertExists('attachments/file.txt');
        $this->assertEquals('attachments/file.txt', $path);
    }

    /** @test */
    public function test_attachment_save_throws_exception_when_no_data()
    {
        Storage::fake('local');

        $body = Mockery::mock();
        $body->shouldReceive('getAttachmentId')->andReturn('attach');
        $body->shouldReceive('getSize')->andReturn(10);

        $part = Mockery::mock(\Google_Service_Gmail_MessagePart::class);
        $part->shouldReceive('getBody')->andReturn($body);
        $part->shouldReceive('getFilename')->andReturn('file.txt');
        $part->shouldReceive('getMimeType')->andReturn('text/plain');
        $part->shouldReceive('getHeaders')->andReturn([]);

        $repository = new StorageTokenRepository;
        $attachment = new Attachment($repository, 'msg', $part);

        $attachmentsResource = Mockery::mock();
        $attachmentData = Mockery::mock();
        $attachmentData->shouldReceive('getData')->andReturn(null);
        $attachmentsResource->shouldReceive('get')->andReturn($attachmentData);

        $service = new \stdClass;
        $service->users_messages_attachments = $attachmentsResource;
        $ref = new ReflectionProperty(Attachment::class, 'service');
        $ref->setAccessible(true);
        $ref->setValue($attachment, $service);

        $this->expectException(\Exception::class);
        $attachment->saveAttachmentTo('attachments', 'file.txt', 'local');
    }

    /** @test */
    public function test_message_method_throws_exception_without_credentials()
    {
        Storage::fake('local');
        $repository = new StorageTokenRepository;
        $client = new LaravelGmailClass($this->app['config'], $repository);

        $this->expectException(AuthException::class);
        $client->message();
    }
}
