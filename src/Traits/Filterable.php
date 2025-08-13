<?php

declare(strict_types=1);

namespace Dacastro4\LaravelGmail\Traits;

use Dacastro4\LaravelGmail\Services\Message;

trait Filterable
{
    /**
     * Filter to get only unread emails
     */
    public function unread(): self|Message
    {
        $this->add('is:unread');

        return $this;
    }

    abstract public function add(string $query, string $column = 'q', bool $encode = true): void;

    public function subject(string $query): self|Message
    {
        $this->add("[{$query}]");

        return $this;
    }

    public function to(string $email): self|Message
    {
        $this->add("to:{$email}");

        return $this;
    }

    public function fromThese(array $emails): self|Message
    {
        $emailsCount = count($emails);
        for ($i = 0; $i < $emailsCount; $i++) {
            ! $i ? $this->add("{from:$emails[$i]") : ($i == $emailsCount - 1 ? $this->add("from:$emails[$i]}") : $this->from($emails[$i]));
        }

        return $this;
    }

    public function from(string $email): self|Message
    {
        $this->add("from:{$email}");

        return $this;
    }

    public function after(string $date): self|Message
    {
        $this->add("after:{$date}");

        return $this;
    }

    public function before(string $date): self|Message
    {
        $this->add("before:{$date}");

        return $this;
    }

    public function raw(string $query): self|Message
    {
        $this->add($query, 'q', false);

        return $this;
    }

    public function in(string $box = 'inbox'): self|Message
    {
        $this->add("in:{$box}");

        return $this;
    }

    public function hasAttachment(): self|Message
    {
        $this->add('has:attachment');

        return $this;
    }
}
