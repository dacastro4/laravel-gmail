<?php

namespace Dacastro4\LaravelGmail\Traits;

trait Trashable
{

	public function trash()
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

	public function untrash()
	{

	}

}