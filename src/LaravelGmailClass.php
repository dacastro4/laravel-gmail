<?php

namespace Dacastro4\LaravelGmail;

use Dacastro4\LaravelGmail\Services\Message;
use Illuminate\Config\Repository as Config;
use Illuminate\Support\Facades\Redirect;

class LaravelGmailClass extends GmailConnection
{

	public function __construct( Config $config )
	{
		parent::__construct( $config );
	}

	/**
	 * @return Message
	 */
	public function message()
	{
		return new Message($this);
	}

	/**
	 * Returns the Gmail user email
	 *
	 * @return \Google_Service_Gmail_Profile
	 */
	public function user()
	{
		return $this->config('email');
	}

	/**
	 * Gets the URL to authorize the user
	 *
	 * @return string
	 */
	public function getAuthUrl()
	{
		return $this->createAuthUrl();
	}

	public function redirect()
	{
		return Redirect::to( $this->getAuthUrl() );
	}

	public function logout()
	{
		$this->revokeToken();
		$this->deleteAccessToken();
	}

}