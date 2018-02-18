<?php

namespace Dacastro4\LaravelGmail\Traits;

use Dacastro4\LaravelGmail\Services\Message\Mail;
use Google_Service_Gmail_ModifyMessageRequest;

trait ModifiesLabels
{

	private $messageRequest;

	public $service;

	public function __construct()
	{
		$this->messageRequest = new Google_Service_Gmail_ModifyMessageRequest();
	}

	/**
	 * Adds labels to the email
	 *
	 * @param string|array $labels
	 *
	 * @return Mail|string
	 * @throws \Exception
	 */
	public function addLabel( $labels )
	{
		if(is_string($labels)) {
			$labels = [$labels];
		}

		$this->messageRequest->setAddLabelIds( $labels );

		try {
			return $this->modify();
		} catch ( \Exception $e ) {
			throw new \Exception( "Couldn't add labels: {$e->getMessage()}" );
		}
	}

	/**
	 * Removes labels from the email
	 *
	 * @param string|array $labels
	 *
	 * @return Mail|string
	 * @throws \Exception
	 */
	public function removeLabel( $labels )
	{
		if(is_string($labels)) {
			$labels = [$labels];
		}

		$this->messageRequest->setRemoveLabelIds( $labels );

		try {
			return $this->modify();
		} catch ( \Exception $e ) {
			throw new \Exception( "Couldn't remove labels: {$e->getMessage()}" );
		}
	}

	/**
	 * Executes the modification
	 *
	 * @return Mail
	 */
	private function modify()
	{
		return new Mail( $this->service->users_messages->modify( 'me', $this->getId(), $this->messageRequest ) );
	}

	public abstract function getId();
}