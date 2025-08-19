<?php

declare(strict_types=1);

namespace Dacastro4\LaravelGmail\Traits;

use Dacastro4\LaravelGmail\Services\Message\Mail;
use Google_Service_Gmail_ModifyMessageRequest;

trait ModifiesLabels
{
    private Google_Service_Gmail_ModifyMessageRequest $messageRequest;

    public function __construct()
    {
        $this->messageRequest = new Google_Service_Gmail_ModifyMessageRequest;
    }

    /**
     * Adds labels to the email
     *
     *
     * @throws \Exception
     */
    public function addLabel(string|array $labels): Mail|string
    {
        if (is_string($labels)) {
            $labels = [$labels];
        }

        $this->messageRequest->setAddLabelIds($labels);

        try {
            return $this->modify();
        } catch (\Exception $e) {
            throw new \Exception("Couldn't add labels: {$e->getMessage()}");
        }
    }

    /**
     * Executes the modification
     */
    private function modify(): Mail
    {
        return new Mail($this->getTokenRepository(), $this->service->users_messages->modify('me', $this->getId(), $this->messageRequest));
    }

    abstract public function getId(): string;

    /**
     * Removes labels from the email
     *
     *
     * @throws \Exception
     */
    public function removeLabel(string|array $labels): Mail|string
    {
        if (is_string($labels)) {
            $labels = [$labels];
        }

        $this->messageRequest->setRemoveLabelIds($labels);

        try {
            return $this->modify();
        } catch (\Exception $e) {
            throw new \Exception("Couldn't remove labels: {$e->getMessage()}");
        }
    }
}
