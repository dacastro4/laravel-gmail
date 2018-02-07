<?php

namespace Dacastro4\LaravelGmail;

use Google_Client;
use Google_Service_Gmail;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Request;

class GmailConnection extends Google_Client
{

	protected $emailAddress;
	protected $refreshToken;
	protected $app;
	protected $accessToken;
	protected $token;
	private $configuration;

	public function __construct( $config = null )
	{
		$this->app = Container::getInstance();
		$this->configuration = $config;
		parent::__construct( $this->getConfigs() );
		$this->setScopes( $this->getUserScopes() );
		$this->setAccessType( 'offline' );
		$this->setApprovalPrompt( 'force' );
		if ( $this->check() ) {
			$this->refreshTokenIfNeeded();
		}
	}

	public function getAccessToken()
	{
		$token = parent::getAccessToken() ?: $this->config();

		return $token;
	}

	/**
	 * @return array|string
	 * @throws \Exception
	 */
	public function makeToken()
	{
		if ( ! $this->check() ) {
			$request = Request::capture();
			$code = $request->input( 'code', null );
			if ( $code ) {
				$accessToken = $this->fetchAccessTokenWithAuthCode( $code );
				$me = $this->getProfile();
				if ( $me ) {
					$this->emailAddress = $me->emailAddress;
				}
				$this->setBothAccessToken( $accessToken );

				return $accessToken;
			} else {
				throw new \Exception( 'No access token' );
			}
		} else {
			return $this->getAccessToken();
		}
	}

	public function setToken( $token )
	{
		$this->setAccessToken( $token );
	}

	public function check()
	{
		return ! $this->isAccessTokenExpired();
	}

	public function isAccessTokenExpired()
	{
		$token = parent::getAccessToken() ?: $this->config();
		if ( $token ) {
			$this->setAccessToken( $token );
		}

		return parent::isAccessTokenExpired();
	}

	public function logout()
	{
		$this->revokeToken();
	}


	private function refreshTokenIfNeeded()
	{
		if ( $this->isAccessTokenExpired() ) {
			$this->fetchAccessTokenWithRefreshToken( $this->getRefreshToken() );
			$token = $this->getAccessToken();
			$this->setAccessToken( $token );
		}
	}

	/**
	 * @return \Google_Service_Gmail_Profile
	 */
	public function getProfile()
	{
		$service = new Google_Service_Gmail( $this );

		return $service->users->getProfile( 'me' );
	}

	public function setAccessToken( $token )
	{
		parent::setAccessToken( $token );
	}

	public function setBothAccessToken( $token )
	{
		parent::setAccessToken( $token );
		$this->saveAccessToken( $token );
	}

	/**
	 * Save the credentials in a file
	 *
	 * @param array $config
	 */
	public function saveAccessToken( array $config )
	{
		$fileName = $this->getFileName();
		$file = "gmail/tokens/$fileName.json";

		if ( Storage::exists( $file ) ) {
			Storage::delete( storage_path( $file ) );
		}

		$config[ 'email' ] = $this->emailAddress;

		Storage::put( $file, json_encode( $config ) );
	}

	/**
	 * Delete the credentials in a file
	 */
	public function deleteAccessToken()
	{
		$fileName = $this->getFileName();
		$file = "gmail/tokens/$fileName.json";

		if ( Storage::exists( $file ) ) {
			Storage::delete( $file );
		}

		Storage::put( $file, json_encode( [] ) );
	}

	/**
	 * @return array
	 */
	public function getConfigs()
	{
		return [
			'client_secret' => $this->configuration[ 'gmail.client_secret' ],
			'client_id'     => $this->configuration[ 'gmail.client_id' ],
			'redirect_uri'  => url( $this->configuration[ 'gmail.redirect_url' ] ),
		];
	}

	public function config( $string = null, $email = null )
	{
		$email = $email ?: $this->emailAddress;
		$fileName = $this->getFileName( $email );
		$file = "gmail/tokens/{$fileName}.json";

		if ( Storage::exists( $file ) ) {
			$config = json_decode(
				Storage::get( $file ),
				true
			);

			if ( $string ) {
				if ( isset( $config[ $string ] ) ) {
					return $config[ $string ];
				}
			} else {
				return $config;
			}

		}

		return null;
	}

	private function getFileName( $email = null )
	{
		//TODO Make the replacer function
		return $this->configuration[ 'gmail.credentials_file_name' ];
	}

	private function getUserScopes()
	{
		return [
			Google_Service_Gmail::GMAIL_READONLY,
			Google_Service_Gmail::GMAIL_MODIFY,
		];
	}

}