<?php

namespace Dacastro4\LaravelGmail\Traits;

use Google_Service_Gmail;
use Illuminate\Support\Facades\Storage;

/**
 * Trait Configurable
 * @package Dacastro4\LaravelGmail\Traits
 */
trait Configurable
{

	private $configuration;

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
		dd( $this->mapScopes() );

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

	abstract function setScopes( $scopes );

	abstract function setAccessType( $type );

	abstract function setApprovalPrompt( $approval );

}