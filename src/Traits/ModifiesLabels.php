<?php

namespace Dacastro4\LaravelGmail\Traits;

use Dacastro4\LaravelGmail\Services\Message\Mail;
use Google_Service_Gmail_ModifyMessageRequest;

trait ModifiesLabels
{

	public $messageRequest;

	public $service;

	public function __construct()
	{
		$this->messageRequest = new Google_Service_Gmail_ModifyMessageRequest();
	}

	/**
	 * @param array $labels
	 *
	 * @return Mail|string
	 * @throws \Exception
	 */
	public function addLabels( array $labels )
	{
		$this->messageRequest->setAddLabelIds( $labels );
		try {
			return $this->modify();
		} catch ( \Exception $e ) {
			throw new \Exception( "Couldn't add labels: {$e->getMessage()}" );
		}
	}

	/**
	 * @param array $labels
	 *
	 * @return Mail|string
	 * @throws \Exception
	 */
	public function removeLabels( array $labels )
	{
		$this->messageRequest->setRemoveLabelIds( $labels );
		try {
			return $this->modify();
		} catch ( \Exception $e ) {
			throw new \Exception( "Couldn't remove labels: {$e->getMessage()}" );
		}
	}

	/**
	 * Adds a single label to the request
	 *
	 * @param $label
	 *
	 * @return Mail
	 */
	private function addSingleLabel( $label )
	{
		$this->messageRequest->setAddLabelIds( [ $label ] );

		return $this->modify();
	}

	/**
	 * Removes a single label from the request
	 *
	 * @param $label
	 *
	 * @return Mail
	 */
	private function removeSingleLabel( $label )
	{
		$this->messageRequest->setRemoveLabelIds( [ $label ] );

		return $this->modify();
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
}