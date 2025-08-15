<?php

declare(strict_types=1);

namespace Dacastro4\LaravelGmail\Services\Message;

use Carbon\Carbon;
use Dacastro4\LaravelGmail\Contracts\TokenRepository;
use Dacastro4\LaravelGmail\GmailConnection;
use Dacastro4\LaravelGmail\Traits\HasDecodableBody;
use Dacastro4\LaravelGmail\Traits\HasParts;
use Dacastro4\LaravelGmail\Traits\Modifiable;
use Dacastro4\LaravelGmail\Traits\Replyable;
use Google_Service_Gmail;
use Google_Service_Gmail_MessagePart;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Class SingleMessage
 */
class Mail extends GmailConnection
{
    use HasDecodableBody,
        HasParts,
        Modifiable,
        Replyable {
            Replyable::__construct as private __rConstruct;
            Modifiable::__construct as private __mConstruct;
        }

    protected string $id = '';

    protected ?int $internalDate = null;

    protected array $labels = [];

    protected ?int $size = null;

    protected ?string $threadId = null;

    protected ?string $historyId = null;

    protected ?Google_Service_Gmail_MessagePart $payload = null;

    protected ?Collection $parts = null;

    protected Google_Service_Gmail $service;

    public function __construct(TokenRepository $tokenRepository, ?\Google_Service_Gmail_Message $message = null, bool $preload = false, ?string $userId = null)
    {
        $this->service = new Google_Service_Gmail($this);

        $this->__rConstruct();
        $this->__mConstruct();
        parent::__construct($tokenRepository, config(), $userId);

        if (! is_null($message)) {
            if ($preload) {
                $message = $this->service->users_messages->get('me', $message->getId());
            }

            $this->setUserId($userId);

            $this->setMessage($message);

            if ($preload) {
                $this->setMetadata();
            }
        }
    }

    protected function setUserId(?string $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * Sets data from mail
     */
    protected function setMessage(\Google_Service_Gmail_Message $message): void
    {
        $this->id = $message->getId();
        $this->internalDate = $message->getInternalDate();
        $this->labels = $message->getLabelIds() ?? [];
        $this->size = $message->getSizeEstimate();
        $this->threadId = $message->getThreadId();
        $this->historyId = $message->getHistoryId();
        $this->payload = $message->getPayload();
        if ($this->payload) {
            $this->parts = collect($this->payload->getParts());
        }
    }

    /**
     * Sets the metadata from Mail when preloaded
     */
    protected function setMetadata(): void
    {
        $this->to = $this->getTo();
        $from = $this->getFrom();
        $this->from = $from['email'] ?? null;
        $this->nameFrom = $from['name'] ?? null;

        $this->subject = $this->getSubject();
    }

    /**
     * Return a UNIX version of the date
     *
     * @return int|null UNIX date
     */
    public function getInternalDate(): ?int
    {
        return $this->internalDate;
    }

    /**
     * Returns the labels of the email
     * Example: INBOX, STARRED, UNREAD
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    /**
     * Returns approximate size of the email
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * Returns thread ID of the email
     */
    public function getThreadId(): ?string
    {
        return $this->threadId;
    }

    /**
     * Returns history ID of the email
     */
    public function getHistoryId(): ?string
    {
        return $this->historyId;
    }

    /**
     * Returns all the headers of the email
     */
    public function getHeaders(): Collection
    {
        return $this->buildHeaders($this->payload->getHeaders());
    }

    /**
     * Returns the subject of the email
     */
    public function getSubject(): ?string
    {
        return $this->getHeader('Subject');
    }

    /**
     * Returns the subject of the email
     */
    public function getReplyTo(): array
    {
        $replyTo = $this->getHeader('Reply-To');

        return $this->getFrom($replyTo ? $replyTo : $this->getHeader('From'));
    }

    /**
     * Returns array of name and email of each recipient
     */
    public function getFrom(?string $email = null): array
    {
        $from = $email ? $email : $this->getHeader('From');

        preg_match('/<(.*)>/', (string) $from, $matches);

        $name = preg_replace('/ <(.*)>/', '', (string) $from);

        return [
            'name' => $name,
            'email' => isset($matches[1]) ? $matches[1] : null,
        ];
    }

    /**
     * Returns email of sender
     */
    public function getFromEmail(): ?string
    {
        $from = $this->getHeader('From');

        if (filter_var($from, FILTER_VALIDATE_EMAIL)) {
            return $from;
        }

        preg_match('/<(.*)>/', (string) $from, $matches);

        return $matches[1] ?? null;
    }

    /**
     * Returns name of the sender
     */
    public function getFromName(): ?string
    {
        $from = (string) $this->getHeader('From');

        $name = preg_replace('/ <(.*)>/', '', $from);

        return $name;
    }

    /**
     * Returns array list of recipients
     */
    public function getTo(): array
    {
        $allTo = $this->getHeader('To');

        return $this->formatEmailList($allTo);
    }

    /**
     * Returns array list of cc recipients
     */
    public function getCc(): array
    {
        $allCc = $this->getHeader('Cc');

        return $this->formatEmailList($allCc);
    }

    /**
     * Returns array list of bcc recipients
     */
    public function getBcc(): array
    {
        $allBcc = $this->getHeader('Bcc');

        return $this->formatEmailList($allBcc);
    }

    /**
     * Returns an array of emails from an string in RFC 822 format
     *
     * @param  string|null  $emails  email list in RFC 822 format
     */
    public function formatEmailList(?string $emails): array
    {
        $all = [];
        $explodedEmails = explode(',', (string) $emails);

        foreach ($explodedEmails as $email) {
            $item = [];

            preg_match('/<(.*)>/', $email, $matches);

            $item['email'] = str_replace(' ', '', $matches[1] ?? $email);

            $name = preg_replace('/ <(.*)>/', '', $email);

            if (Str::startsWith($name, ' ')) {
                $name = substr($name, 1);
            }

            $item['name'] = str_replace('"', '', $name ?: null);

            $all[] = $item;
        }

        return $all;
    }

    /**
     * Returns the original date that the email was sent
     */
    public function getDate(): Carbon
    {
        return Carbon::parse($this->getHeader('Date'));
    }

    /**
     * Returns email of the original recipient
     */
    public function getDeliveredTo(): ?string
    {
        return $this->getHeader('Delivered-To');
    }

    /**
     * Base64 version of the body
     */
    public function getRawPlainTextBody(): string
    {
        return $this->getPlainTextBody(true);
    }

    public function getPlainTextBody(bool $raw = false): string
    {
        $content = (string) $this->getBody();

        return $raw ? $content : $this->getDecodedBody($content);
    }

    public function getBody(string $type = 'text/plain'): ?string
    {
        $parts = $this->getAllParts($this->parts);

        try {
            if (! $parts->isEmpty()) {
                foreach ($parts as $part) {
                    if ($part->mimeType == $type) {
                        return $part->body->data;
                    } elseif ($this->payload->body->data) {
                        return $this->payload->body->data;
                    }
                }
            } else {
                return $this->payload->body->data;
            }
        } catch (\Exception $exception) {
            throw new \Exception('Preload or load the single message before getting the body.');
        }

        return null;
    }

    public function hasAttachments(): bool
    {
        $parts = $this->getAllParts($this->parts);
        $has = false;

        /** @var Google_Service_Gmail_MessagePart $part */
        foreach ($parts as $part) {
            if (! empty($part->body->attachmentId) && $part->getFilename() != null && strlen($part->getFilename()) > 0) {
                $has = true;
                break;
            }
        }

        return $has;
    }

    public function countAttachments(): int
    {
        $numberOfAttachments = 0;
        $parts = $this->getAllParts($this->parts);

        foreach ($parts as $part) {
            if (! empty($part->body->attachmentId)) {
                $numberOfAttachments++;
            }
        }

        return $numberOfAttachments;
    }

    public function getDecodedBody(string $content): string
    {
        $content = str_replace('_', '/', str_replace('-', '+', $content));

        return base64_decode($content);
    }

    public function getRawHtmlBody(): string
    {
        return $this->getHtmlBody(true);
    }

    public function getHtmlBody(bool $raw = false): string
    {
        $content = (string) $this->getBody('text/html');

        return $raw ? $content : $this->getDecodedBody($content);
    }

    public function getAttachmentsWithData(): Collection
    {
        return $this->getAttachments(true);
    }

    public function getAttachments(bool $preload = false): Collection
    {
        $attachments = new Collection;
        $parts = $this->getAllParts($this->parts);

        foreach ($parts as $part) {
            if (! empty($part->body->attachmentId)) {
                $attachment = new Attachment($this->getTokenRepository(), $part->body->attachmentId, $part, $this->userId);

                if ($preload) {
                    $attachment = $attachment->getData();
                }

                $attachments->push($attachment);
            }
        }

        return $attachments;
    }

    public function getPayload(): ?Google_Service_Gmail_MessagePart
    {
        return $this->payload;
    }

    public function getParts(): ?Collection
    {
        return $this->parts;
    }

    public function getService(): Google_Service_Gmail
    {
        return $this->service;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUser(): ?string
    {
        return $this->config('email');
    }

    public function load(): Mail
    {
        $message = $this->service->users_messages->get('me', $this->getId());

        return new self($message);
    }

    public function using(string $token): Mail
    {
        $this->setToken($token);

        return $this;
    }

    public function hasParts(): bool
    {
        return (bool) $this->iterateParts($this->parts, $returnOnFirstFound = true);
    }

    private function buildHeaders(array $emailHeaders): Collection
    {
        $headers = [];

        foreach ($emailHeaders as $header) {
            /** @var \Google_Service_Gmail_MessagePartHeader $header */
            $head = new \stdClass;

            $head->key = $header->getName();
            $head->value = $header->getValue();

            $headers[] = $head;
        }

        return collect($headers);
    }
}
