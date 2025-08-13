<?php

declare(strict_types=1);

namespace Dacastro4\LaravelGmail\Services\Message;

use Dacastro4\LaravelGmail\GmailConnection;
use Dacastro4\LaravelGmail\Traits\HasDecodableBody;
use Google_Service_Gmail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Attachment extends GmailConnection
{
    use HasDecodableBody;

    public mixed $body = null;

    public string $id;

    public string $filename;

    public string $mimeType;

    public int $size;

    public array $headerDetails;

    private array $headers = [];

    public mixed $service;

    private string $messageId;

    public function __construct(string $singleMessageId, \Google_Service_Gmail_MessagePart $part, ?string $userId = null)
    {
        parent::__construct(config(), $userId);

        $this->service = new Google_Service_Gmail($this);

        $body = $part->getBody();
        $this->id = $body->getAttachmentId();
        $this->size = $body->getSize();
        $this->filename = $part->getFilename();
        $this->mimeType = $part->getMimeType();
        $this->messageId = $singleMessageId;
        $headers = $part->getHeaders();
        $this->headerDetails = $this->getHeaderDetails($headers);
    }

    /**
     * Retuns attachment ID
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Returns attachment file name
     *
     * @return string
     */
    public function getFileName(): string
    {
        return $this->filename;
    }

    /**
     * Returns mime type of the attachment
     *
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Returns approximate size of the attachment
     *
     * @return mixed
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @param  string  $path
     * @param  string|null  $filename
     * @param  string  $disk
     * @return string
     *
     * @throws \Exception
     */
    public function saveAttachmentTo(?string $path = null, ?string $filename = null, string $disk = 'local'): string
    {
        $dataRaw = $this->getData();

        if (! $dataRaw) {
            throw new \Exception('Could not get the attachment.');
        }

        $data = $this->getDecodedBody($dataRaw);

        $filename = $filename ?: $this->filename;

        if (is_null($path)) {
            $path = '/';
        } else {
            if (! Str::endsWith('/', $path)) {
                $path = "{$path}/";
            }
        }

        $filePathAndName = "{$path}{$filename}";

        Storage::disk($disk)->put($filePathAndName, $data);

        return $filePathAndName;
    }

    /**
     * @throws \Exception
     */
    public function getData(): ?string
    {
        $attachment = $this->service->users_messages_attachments->get('me', $this->messageId, $this->id);

        return $attachment->getData();
    }

    /**
     * Returns attachment headers
     * Contains Content-ID and X-Attachment-Id for embedded images
     *
     * @return array
     */
    public function getHeaderDetails(array $headers): array
    {
        $headerDetails = [];

        foreach ($headers as $header) {
            $headerDetails[$header->name] = $header->value;
        }

        return $headerDetails;
    }
}
