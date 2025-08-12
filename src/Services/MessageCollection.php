<?php

namespace Dacastro4\LaravelGmail\Services;

use Illuminate\Support\Collection;

class MessageCollection extends Collection
{
    private ?Message $message;

    /**
     * MessageCollection constructor.
     */
    public function __construct(array $items = [], ?Message $message = null)
    {
        parent::__construct($items);
        $this->message = $message;
    }

    public function next(): Collection
    {
        return $this->message->next();
    }

    /**
     * Returns boolean if the page token variable is null or not
     */
    public function hasNextPage(): bool
    {
        return $this->message?->hasNextPage() ?? false;
    }

    /**
     * Returns the page token or null
     */
    public function getPageToken(): ?string
    {
        return $this->message?->getPageToken();
    }
}
