<?php

declare(strict_types=1);

namespace Dacastro4\LaravelGmail;

use Dacastro4\LaravelGmail\Contracts\TokenRepository;
use Dacastro4\LaravelGmail\Traits\Configurable;
use Dacastro4\LaravelGmail\Traits\HasLabels;
use Google_Client;
use Google_Service_Gmail;
use Illuminate\Container\Container;
use Illuminate\Http\Request;

class GmailConnection extends Google_Client
{
    use Configurable {
        __construct as configConstruct;
    }
    use HasLabels;

    protected ?string $emailAddress = null;

    protected ?string $refreshToken = null;

    protected Container $app;

    protected mixed $accessToken = null;

    protected mixed $token = null;

    private mixed $configuration;

    public ?string $userId = null;

    protected TokenRepository $tokenRepository;

    public function __construct(TokenRepository $tokenRepository, mixed $config = null, ?string $userId = null)
    {
        $this->app = Container::getInstance();

        $this->tokenRepository = $tokenRepository;

        $this->userId = $userId;

        $this->configConstruct($config);

        $this->configuration = $config;

        parent::__construct($this->getConfigs());

        $this->configApi();

        if ($this->checkPreviouslyLoggedIn()) {
            $this->refreshTokenIfNeeded();
        }

    }

    public function getTokenRepository(): TokenRepository
    {
        return $this->tokenRepository;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    /**
     * Check and return true if the user has previously logged in without checking if the token needs to refresh
     */
    public function checkPreviouslyLoggedIn(): bool
    {
        $fileName = $this->getFileName();
        $allowJsonEncrypt = $this->_config['gmail.allow_json_encrypt'];

        if (! $this->tokenRepository->tokenExists($fileName)) {
            return false;
        }

        $savedConfigToken = $this->tokenRepository->getToken($fileName, $allowJsonEncrypt);

        return ! empty($savedConfigToken['access_token']);
    }

    /**
     * Refresh the auth token if needed
     *
     * @return mixed|null
     */
    private function refreshTokenIfNeeded(): mixed
    {
        if ($this->isAccessTokenExpired()) {
            $this->fetchAccessTokenWithRefreshToken($this->getRefreshToken());
            $token = $this->getAccessToken();
            $this->setBothAccessToken($token);

            return $token;
        }

        return $this->token;
    }

    /**
     * Check if token exists and is expired
     * Throws an AuthException when the auth file its empty or with the wrong token
     *
     *
     * @return bool Returns True if the access_token is expired.
     */
    public function isAccessTokenExpired(): bool
    {
        $token = $this->getToken();

        if ($token) {
            $this->setAccessToken($token);
        }

        return parent::isAccessTokenExpired();
    }

    public function getToken(): mixed
    {
        return parent::getAccessToken() ?: $this->config();
    }

    public function setToken(mixed $token): void
    {
        $this->setAccessToken($token);
    }

    public function getAccessToken(): mixed
    {
        $token = parent::getAccessToken() ?: $this->config();

        return $token;
    }

    /**
     * @param  array|string  $token
     */
    public function setAccessToken($token)
    {
        parent::setAccessToken($token);
    }

    public function setBothAccessToken(array|string $token): void
    {
        $this->setAccessToken($token);
        $this->saveAccessToken($token);
    }

    /**
     * Save the credentials in a file
     */
    public function saveAccessToken(array $config): void
    {
        $fileName = $this->getFileName();
        $allowJsonEncrypt = $this->_config['gmail.allow_json_encrypt'];
        $config['email'] = $this->emailAddress;

        $this->tokenRepository->storeToken($fileName, $config, $allowJsonEncrypt);
    }

    /**
     * @throws \Exception
     */
    public function makeToken(Request $request): array|string
    {
        if (! $this->check()) {
            $code = (string) $request->input('code', null);
            if (! is_null($code) && ! empty($code)) {
                $accessToken = $this->fetchAccessTokenWithAuthCode($code);
                if ($this->haveReadScope()) {
                    $me = $this->getProfile();
                    if (property_exists($me, 'emailAddress')) {
                        $this->emailAddress = $me->emailAddress;
                        $accessToken['email'] = $me->emailAddress;
                    }
                }
                $this->setBothAccessToken($accessToken);

                return $accessToken;
            } else {
                throw new \Exception('No access token');
            }
        } else {
            return $this->getAccessToken();
        }
    }

    /**
     * Check
     */
    public function check(): bool
    {
        return ! $this->isAccessTokenExpired();
    }

    /**
     * Gets user profile from Gmail
     */
    public function getProfile(): \Google_Service_Gmail_Profile
    {
        $service = new Google_Service_Gmail($this);

        return $service->users->getProfile('me');
    }

    /**
     * Revokes user's permission and logs them out
     */
    public function logout(): void
    {
        $this->revokeToken();
    }

    /**
     * Delete the credentials in a file
     */
    public function deleteAccessToken(): void
    {
        $fileName = $this->getFileName();
        $allowJsonEncrypt = $this->_config['gmail.allow_json_encrypt'];

        $this->tokenRepository->deleteToken($fileName, $allowJsonEncrypt);
    }

    private function haveReadScope(): bool
    {
        $scopes = $this->getUserScopes();

        return in_array(Google_Service_Gmail::GMAIL_READONLY, $scopes);
    }

    /**
     * users.stop receiving push notifications for the given user mailbox.
     *
     * @param  string  $userEmail  Email address
     */
    public function stopWatch(string $userEmail, array $optParams = []): \Google_Service_Gmail_Stop
    {
        $service = new Google_Service_Gmail($this);

        return $service->users->stop($userEmail, $optParams);
    }

    /**
     * Set up or update a push notification watch on the given user mailbox.
     *
     * @param  string  $userEmail  Email address
     */
    public function setWatch(string $userEmail, \Google_Service_Gmail_WatchRequest $postData): \Google_Service_Gmail_WatchResponse
    {
        $service = new Google_Service_Gmail($this);

        return $service->users->watch($userEmail, $postData);
    }

    /**
     * Lists the history of all changes to the given mailbox. History results are returned in chronological order (increasing historyId).
     */
    public function historyList(string $userEmail, array $params): \Google\Service\Gmail\ListHistoryResponse
    {
        $service = new Google_Service_Gmail($this);

        return $service->users_history->listUsersHistory($userEmail, $params);
    }
}
