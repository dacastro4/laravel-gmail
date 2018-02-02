<?php

namespace Dacastro4\LaravelGmail\Services;

use Dacastro4\LaravelGmail\LaravelGmailClass;
use Dacastro4\LaravelGmail\ServiceAbstract;
use Dacastro4\LaravelGmail\Services\Message\Mail;
use Dacastro4\LaravelGmail\Traits\SendsParameters;
use Google_Service_Gmail;

class Message implements ServiceAbstract
{

	use SendsParameters;

	public $service;
	public $preload = false;
	protected $params = [];

	public function __construct( LaravelGmailClass $client )
	{
		$this->service = new Google_Service_Gmail( $client );
	}

	/**
	 * @param $id
	 *
	 * @return Mail
	 */
	public function get( $id )
	{
		$message = $this->service->users_messages->get( 'me', $id );

		return new Mail( $message );
	}

	public function unread()
	{
		$this->add('is%3Aunread' );

		return $this;
	}

	public function from( $email )
	{
		$this->add("from:{$email}");

		return $this;
	}

	public function preload()
	{
		$this->preload = true;

		return $this;
	}

	/**
	 * Valid boxes
	 * * starred
	 * * inbox
	 * * spam
	 * * chats
	 * * sent
	 * * draft
	 * * trash
	 *
	 * @param $box
	 *
	 * @return Message
	 */
	public function in( $box = 'inbox' )
	{
		$this->add("in%3A{$box}" );

		return $this;
	}

	/**
	 * Determines if the Mail has attachments
	 *
	 * @return $this
	 */
	public function hasAttachment()
	{
		$this->add('has%3Aattachment');

		return $this;
	}

	/**
	 * Returns a collection of Mail instances
	 *
	 * @param null $pageToken
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function list( $pageToken = null )
	{
		if ( $pageToken ) {
			$this->add($pageToken, 'pageToken');
		}

		$messages = [];
		$allMessages = $this->service->users_messages->listUsersMessages( 'me', $this->params )->getMessages();

		foreach ($allMessages as $message) {
			$messages[] = new Mail($message, $this->preload);
		}

		return collect($messages);
	}
}