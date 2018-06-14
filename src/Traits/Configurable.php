<?php

namespace Dacastro4\LaravelGmail\Traits;

use Google_Service_Gmail;

/**
 * Trait Configurable
 * @package Dacastro4\LaravelGmail\Traits
 */
trait Configurable
{

	private $_config;

	public function __construct( $config )
	{
		$this->_config = $config;
	}

	public function config( $string = null )
	{
		$fileName = $this->getFileName();
		$file = storage_path( "app/gmail/tokens/{$fileName}.json" );

		if ( file_exists( $file ) ) {
			$config = json_decode(
				file_get_contents( $file ),
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

	/**
	 * @return array
	 */
	public function getConfigs()
	{
		return [
			'client_secret' => $this->_config[ 'gmail.client_secret' ],
			'client_id'     => $this->_config[ 'gmail.client_id' ],
			'redirect_uri'  => url( $this->_config[ 'gmail.redirect_url' ] ),
		];
	}

	private function getUserScopes()
	{
		return array_merge(
			[
				Google_Service_Gmail::GMAIL_READONLY,
			], $this->mapScopes() );
	}

	private function configApi()
	{
		$type = $this->_config[ 'gmail.access_type' ];
		$approval_prompt = $this->_config[ 'gmail.approval_prompt' ];

		$this->setScopes( $this->getUserScopes() );

		$this->setAccessType( $type );

		$this->setApprovalPrompt( $approval_prompt );
	}

	private function getFileName()
	{
		return $this->_config[ 'gmail.credentials_file_name' ];
	}

	private function mapScopes()
	{
		$scopes = $this->_config[ 'gmail.scopes' ];
		$mappedScopes = [];

		if ( ! empty( $scopes ) ) {
			foreach ( $scopes as $scope ) {
				$mappedScopes[] = $this->scopeMap( $scope );
			}
		}

		return $mappedScopes;
	}

	private function scopeMap( $scope )
	{
		$scopes = [
			'all'              => Google_Service_Gmail::MAIL_GOOGLE_COM,
			'compose'          => Google_Service_Gmail::GMAIL_COMPOSE,
			'insert'           => Google_Service_Gmail::GMAIL_INSERT,
			'labels'           => Google_Service_Gmail::GMAIL_LABELS,
			'metadata'         => Google_Service_Gmail::GMAIL_METADATA,
			'modify'           => Google_Service_Gmail::GMAIL_MODIFY,
			'readonly'         => Google_Service_Gmail::GMAIL_READONLY,
			'send'             => Google_Service_Gmail::GMAIL_SEND,
			'settings_basic'   => Google_Service_Gmail::GMAIL_SETTINGS_BASIC,
			'settings_sharing' => Google_Service_Gmail::GMAIL_SETTINGS_SHARING,
		];

		return array_get( $scopes, $scope );
	}

	public abstract function setScopes( $scopes );

	public abstract function setAccessType( $type );

	public abstract function setApprovalPrompt( $approval );

}