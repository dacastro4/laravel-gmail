<?php

namespace Dacastro4\LaravelGmail;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class LaravelGmailClass extends GmailConnection
{

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * @param $name
	 * @param $arguments
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function __call( $name, $arguments )
	{
		if ( method_exists( $this, $name ) ) {
			call_user_func_array( [
				                      $this,
				                      $name ], $arguments );
		} else {
			$className = "Dacastro4\\LaravelGmail\\Services\\{$name}";
			if ( class_exists( $className ) ) {
				try {
					$class = new \ReflectionClass( $className );
					$instance = $class->newInstanceArgs( [ $this ] );

					return $instance;
				} catch ( \ReflectionException $e ) {
					throw new \Exception( "Class Not Found {$name}" );
				}
			}

			return null;
		}
	}

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
		$this->setAccessToken( 'null' );
	}

}