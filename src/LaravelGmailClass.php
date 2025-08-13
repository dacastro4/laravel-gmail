<?php

declare(strict_types=1);

namespace Dacastro4\LaravelGmail;

use Dacastro4\LaravelGmail\Exceptions\AuthException;
use Dacastro4\LaravelGmail\Services\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class LaravelGmailClass extends GmailConnection
{
    public function __construct(mixed $config, ?string $userId = null)
    {
        if (class_basename($config) === 'Application') {
            $config = $config['config'];
        }

        parent::__construct($config, $userId);
    }

    /**
     * @throws AuthException
     */
    public function message(): Message
    {
        if (! $this->getToken()) {
            throw new AuthException('No credentials found.');
        }

        return new Message($this);
    }

    /**
     * Returns the Gmail user's email
     */
    public function user(): string
    {
        return $this->config('email');
    }

    /**
     * Updates / sets the current userId for the service
     *
     * @return \Google_Service_Gmail_Profile
     */
    public function setUserId(string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function redirect(): RedirectResponse
    {
        return Redirect::to($this->getAuthUrl());
    }

    /**
     * Gets the URL to authorize the user
     */
    public function getAuthUrl(): string
    {
        return $this->createAuthUrl();
    }

    public function logout(): void
    {
        $this->revokeToken();
        $this->deleteAccessToken();
    }
}
