<?php

namespace Dacastro4\LaravelGmail\Traits;

use Google_Service_Gmail;
use Illuminate\Support\Facades\Storage;

/**
 * Trait Configurable
 * @package Dacastro4\LaravelGmail\Traits
 * @property $configuration
 * @property $emailAddress
 */
trait Configurable
{

	public function config( $string = null )
	{
		$fileName = $this->getFileName();
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

	private function getUserScopes()
	{
		return array_merge(
			[
				Google_Service_Gmail::GMAIL_READONLY,
			], $this->mapScopes() );
	}

	private function configApi()
	{
		$type = $this->configuration[ 'gmail.access_type' ];
		$approval_prompt = $this->configuration[ 'gmail.approval_prompt' ];

		$this->setScopes( $this->getUserScopes() );

		$this->setAccessType( $type );

		$this->setApprovalPrompt( $approval_prompt );
	}

	private function getFileName()
	{
		return $this->configuration[ 'gmail.credentials_file_name' ];
	}

	private function mapScopes()
	{
		$scopes = $this->configuration[ 'gmail.scopes' ];
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
		switch ( $scope ) {
			case 'all':
				return Google_Service_Gmail::MAIL_GOOGLE_COM;
			case 'compose':
				return Google_Service_Gmail::GMAIL_COMPOSE;
			case 'insert':
				return Google_Service_Gmail::GMAIL_INSERT;
			case 'labels':
				return Google_Service_Gmail::GMAIL_LABELS;
			case 'metadata':
				return Google_Service_Gmail::GMAIL_METADATA;
			case 'modify':
				return Google_Service_Gmail::GMAIL_MODIFY;
			case 'readonly':
				return Google_Service_Gmail::GMAIL_READONLY;
			case 'send':
				return Google_Service_Gmail::GMAIL_SEND;
			case 'settings_basic':
				return Google_Service_Gmail::GMAIL_SETTINGS_BASIC;
			case 'settings_sharing':
				return Google_Service_Gmail::GMAIL_SETTINGS_SHARING;
		}
	}

}