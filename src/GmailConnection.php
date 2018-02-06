<?php

namespace Dacastro4\LaravelGmail;

use Google_Client;
use Google_Service_Gmail;
use Illuminate\Container\Container;
use Illuminate\Config\Repository as Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;

class GmailConnection extends Google_Client
{

	protected $emailAddress;
	protected $refreshToken;
	protected $app;
	protected $accessToken;
	protected $token;
	private $config;

	public function __construct( Config $config = null )
	{
		$this->app = Container::getInstance();
		$this->config = $config;
		parent::__construct( $this->getConfigs() );
		$this->setScopes( $this->getUserScopes() );
		$this->setAccessType( 'offline' );
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
				$this->setAccessToken( $accessToken );

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
		$file = $file = storage_path( $fileName );

		File::delete( $file );

		$config[ 'email' ] = $this->emailAddress;
		File::put( $file, json_encode( $config ) );
	}

	/**
	 * Delete the credentials in a file
	 */
	public function deleteAccessToken()
	{
		$fileName = $this->getFileName();
		$file = $file = storage_path( $fileName );

		File::delete( $file );

		File::put( $file, json_encode( [] ) );
	}

	/**
	 * @return array
	 */
	public function getConfigs()
	{
		return [
			'client_secret' => $this->config->get( 'gmail.client_secret' ),
			'client_id'     => $this->config->get( 'gmail.client_id' ),
			'redirect_uri'  => url( $this->config->get( 'gmail.redirect_url', '/' ) ),
		];
	}

	public function config( $string = null, $email = null )
	{
		$email = $email ?: $this->emailAddress;
		$file = storage_path( "gmail-{$email}json" );

		if ( File::exists( $file ) ) {
			$config = json_decode(
				File::get( $file ),
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

	private function getFileName()
	{
		//TODO Make the replacer function
		return $this->config->get( 'gmail.credentials_file_name' );
	}

	private function getUserScopes()
	{
		return [
			Google_Service_Gmail::GMAIL_READONLY,
			Google_Service_Gmail::GMAIL_MODIFY,
		];
	}

}