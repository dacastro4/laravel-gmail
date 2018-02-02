<?php

namespace Dacastro4\LaravelGmail\Traits;
use Google_Service_Gmail;
use Google_Service_Gmail_ModifyMessageRequest;

/**
 * @property Google_Service_Gmail $service
 */
trait Modifiable
{

	public function markAsRead()
	{
		$body = new Google_Service_Gmail_ModifyMessageRequest();
		$body->setRemoveLabelIds( [ 'UNREAD' ] );
		try {
			$message = $this->service->users_messages->modify( 'me', $this->getId(), $body );
		} catch ( \Exception $e ) {
			return "Couldn't mark email as read: {$e->getMessage()}";
		}

		return true;
	}

	public function markAsUnread()
	{
		$body = new Google_Service_Gmail_ModifyMessageRequest();
		$body->setAddLabelIds( [ 'UNREAD' ] );
		try {
			$message = $this->service->users_messages->modify( 'me', $this->getId(), $body );
		} catch ( \Exception $e ) {
			return "Couldn't mark email as unread: {$e->getMessage()}";
		}

		return true;
	}

	//TODO: Mark and unmark IMPORTANT
	public function asImportant()
	{

	}

	public function asNotImportant()
	{

	}

	//TODO: Mark and unmark STARRED
	public function star()
	{

	}

	public function unStart()
	{

	}

	//TODO: add labels function
	public function addLabels( $labels )
	{

	}

	public function removeLabels( $labels )
	{

	}

	//TODO: Change labels function
	public function changeLabels( $newBox )
	{
		$body = new Google_Service_Gmail_ModifyMessageRequest();
		$body->setAddLabelIds( [ 'UNREAD' ] );
		try {
			$message = $this->service->users_messages->modify( 'me', $this->getId(), $body );
		} catch ( \Exception $e ) {
			return "Couldn't mark email as unread: {$e->getMessage()}";
		}

		return true;
	}

}