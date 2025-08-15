<?php

declare(strict_types=1);

namespace Dacastro4\LaravelGmail\Services;

use Dacastro4\LaravelGmail\LaravelGmailClass;
use Dacastro4\LaravelGmail\Services\Message\Mail;
use Dacastro4\LaravelGmail\Traits\Filterable;
use Dacastro4\LaravelGmail\Traits\SendsParameters;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;
use Illuminate\Support\Collection;

class Message
{
    use Filterable,
        SendsParameters;

    public mixed $service;

    private bool $preload = false;

    private ?string $pageToken = null;

    private LaravelGmailClass $client;

    /**
     * Optional parameter for getting single and multiple emails
     */
    protected array $params = [];

    /**
     * Message constructor.
     */
    public function __construct(LaravelGmailClass $client)
    {
        $this->client = $client;
        $this->service = new Google_Service_Gmail($client);
    }

    /**
     * Returns next page if available of messages or an empty collection
     *
     * @throws \Google_Exception
     */
    public function next(): Collection
    {
        return $this->pageToken
            ? $this->all($this->pageToken)
            : new MessageCollection([], $this);
    }

    /**
     * Returns a collection of Mail instances
     *
     * @throws \Google_Exception
     */
    public function all(?string $pageToken = null): Collection
    {
        if (! is_null($pageToken)) {
            $this->add($pageToken, 'pageToken');
        }

        $mails = [];
        $response = $this->getMessagesResponse();
        $this->pageToken = method_exists($response, 'getNextPageToken') ? $response->getNextPageToken() : null;

        $messages = $response->getMessages();

        if (! $this->preload) {
            foreach ($messages as $message) {
                $mails[] = new Mail($this->client->getTokenRepository(), $message, $this->preload, $this->client->userId);
            }
        } else {
            $mails = count($messages) > 0 ? $this->batchRequest($messages) : [];
        }

        return new MessageCollection($mails, $this);
    }

    /**
     * Returns boolean if the page token variable is null or not
     */
    public function hasNextPage(): bool
    {
        return (bool) $this->pageToken;
    }

    public function getPageToken(): ?string
    {
        return $this->pageToken;
    }

    /**
     * Limit the messages coming from the query
     */
    public function take(int $number): self
    {
        $this->params['maxResults'] = abs($number);

        return $this;
    }

    public function get(string $id): Mail
    {
        $message = $this->getRequest($id);

        return new Mail($this->client->getTokenRepository(), $message, false, $this->client->userId);
    }

    /**
     * Creates a batch request to get all emails in a single call
     */
    public function batchRequest(array $allMessages): array
    {
        $this->client->setUseBatch(true);

        $batch = $this->service->createBatch();

        foreach ($allMessages as $key => $message) {
            $batch->add($this->getRequest($message->getId()), $key);
        }

        $messagesBatch = $batch->execute();

        $this->client->setUseBatch(false);

        $messages = [];

        foreach ($messagesBatch as $message) {
            $messages[] = new Mail($this->client->getTokenRepository(), $message, false, $this->client->userId);
        }

        return $messages;
    }

    /**
     * Preload the information on each Mail objects.
     * If is not preload you will have to call the load method from the Mail class
     *
     * @see Mail::load()
     */
    public function preload(): self
    {
        $this->preload = true;

        return $this;
    }

    public function getUser(): string
    {
        return $this->client->user();
    }

    private function getRequest(string $id): Google_Service_Gmail_Message
    {
        return $this->service->users_messages->get('me', $id);
    }

    /**
     * @throws \Google_Exception
     */
    private function getMessagesResponse(): mixed
    {
        $responseOrRequest = $this->service->users_messages->listUsersMessages('me', $this->params);

        if ($responseOrRequest instanceof \GuzzleHttp\Psr7\Request) {
            $response = $this->service->getClient()->execute(
                $responseOrRequest,
                'Google_Service_Gmail_ListMessagesResponse'
            );

            return $response;
        }

        return $responseOrRequest;
    }
}
