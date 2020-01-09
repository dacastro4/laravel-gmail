<?php

namespace Dacastro4\LaravelGmail;

use Dacastro4\LaravelGmail\Traits\Configurable;
use Google_Client;
use Google_Service_Gmail;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;

class GmailConnection extends Google_Client
{

	use Configurable {
		__construct as configConstruct;
	}

	protected $emailAddress;
	protected $refreshToken;
	protected $app;
	protected $accessToken;
	protected $token;
	private $configuration;
	public $userId;

	public function __construct($config = null, $userId = null)
	{
		$this->app = Container::getInstance();

		$this->userId = $userId;

		$this->configConstruct($config);

		$this->configuration = $config;

		parent::__construct($this->getConfigs());

		$this->configApi();

		if ($this->checkPreviouslyLoggedIn()) {
			$this->refreshTokenIfNeeded();
		}

	}

	/**
	 * Check and return true if the user has previously logged in without checking if the token needs to refresh
	 *
	 * @return bool
	 */
	public function checkPreviouslyLoggedIn()
	{
		$fileName = $this->getFileName();
		$file = "gmail/tokens/$fileName.json";
		$allowJsonEncrypt = $this->_config['gmail.allow_json_encrypt'];

		if (Storage::disk('local')->exists($file)) {
			if ($allowJsonEncrypt) {
				$savedConfigToken = json_decode(decrypt(Storage::disk('local')->get($file)), true);
			} else {
				$savedConfigToken = json_decode(Storage::disk('local')->get($file), true);
			}

			return !empty($savedConfigToken['access_token']);

		}

		return false;
	}

	/**
	 * Refresh the auth token if needed
	 *
	 * @return mixed|null
	 */
	private function refreshTokenIfNeeded()
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
	public function isAccessTokenExpired()
	{
		$token = $this->getToken();

		if ($token) {
			$this->setAccessToken($token);
		}

		return parent::isAccessTokenExpired();
	}

	public function getToken()
	{
		return parent::getAccessToken() ?: $this->config();
	}

	public function setToken($token)
	{
		$this->setAccessToken($token);
	}

	public function getAccessToken()
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

	/**
	 * @param $token
	 */
	public function setBothAccessToken($token)
	{
		$this->setAccessToken($token);
		$this->saveAccessToken($token);
	}

	/**
	 * Save the credentials in a file
	 *
	 * @param  array  $config
	 */
	public function saveAccessToken(array $config)
	{
		$disk = Storage::disk('local');
		$fileName = $this->getFileName();
		$file = "gmail/tokens/$fileName.json";
		$allowJsonEncrypt = $this->_config['gmail.allow_json_encrypt'];
		$config['email'] = $this->emailAddress;

		if ($disk->exists($file)) {

			if (empty($config['email'])) {
				if ($allowJsonEncrypt) {
					$savedConfigToken = json_decode(decrypt($disk->get($file)), true);
				} else {
					$savedConfigToken = json_decode($disk->get($file), true);
				}
				if(isset( $savedConfigToken['email'])) {
					$config['email'] = $savedConfigToken['email'];
				}
			}

			$disk->delete($file);
		}

		if ($allowJsonEncrypt) {
			$disk->put($file, encrypt(json_encode($config)));
		} else {
			$disk->put($file, json_encode($config));
		}

	}

	/**
	 * @return array|string
	 * @throws \Exception
	 */
	public function makeToken()
	{
		if (!$this->check()) {
			$request = Request::capture();
			$code = (string) $request->input('code', null);
			if (!is_null($code) && !empty($code)) {
				$accessToken = $this->fetchAccessTokenWithAuthCode($code);
				if($this->haveReadScope()) {
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
	 *
	 * @return bool
	 */
	public function check()
	{
		return !$this->isAccessTokenExpired();
	}

	/**
	 * Gets user profile from Gmail
	 *
	 * @return \Google_Service_Gmail_Profile
	 */
	public function getProfile()
	{
		$service = new Google_Service_Gmail($this);

		return $service->users->getProfile('me');
	}

	/**
	 * Revokes user's permission and logs them out
	 */
	public function logout()
	{
		$this->revokeToken();
	}

	/**
	 * Delete the credentials in a file
	 */
	public function deleteAccessToken()
	{
		$disk = Storage::disk('local');
		$fileName = $this->getFileName();
		$file = "gmail/tokens/$fileName.json";

		$allowJsonEncrypt = $this->_config['gmail.allow_json_encrypt'];

		if ($disk->exists($file)) {
			$disk->delete($file);
		}

		if ($allowJsonEncrypt) {
			$disk->put($file, encrypt(json_encode([])));
		} else {
			$disk->put($file, json_encode([]));
		}

	}

	private function haveReadScope()
	{
		$scopes = $this->getUserScopes();

		return in_array(Google_Service_Gmail::GMAIL_READONLY, $scopes);
	}

}
