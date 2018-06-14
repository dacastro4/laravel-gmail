<?php

namespace Dacastro4\LaravelGmail\Services;

use Dacastro4\LaravelGmail\LaravelGmailClass;
use Dacastro4\LaravelGmail\Services\Message\Mail;
use Dacastro4\LaravelGmail\Traits\SendsParameters;
use Google_Service_Gmail;

class Message
{

	use SendsParameters;

	public $service;

	public $preload = false;

	/**
	 * Optional parameter for getting single and multiple emails
	 *
	 * @var array
	 */
	protected $params = [];

	/**
	 * Message constructor.
	 *
	 * @param LaravelGmailClass $client
	 */
	public function __construct( LaravelGmailClass $client )
	{
		$this->service = new Google_Service_Gmail( $client );
	}

	/**
	 * Returns a collection of Mail instances
	 *
	 * @param null|string $pageToken
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function all( $pageToken = null )
	{
		if ( !is_null($pageToken) ) {
			$this->add( $pageToken, 'pageToken' );
		}

		$messages = [];
		$allMessages = $this->service->users_messages->listUsersMessages( 'me', $this->params )->getMessages();

		foreach ( $allMessages as $message ) {
			$messages[] = new Mail( $message, $this->preload );
		}

		return collect( $messages );
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

	/**
	 * Filter to get only unread emalis
	 *
	 * @return $this
	 */
	public function unread()
	{
		$this->add( 'is:unread' );

		return $this;
	}

	/**
	 * Filter to get only unread emalis
	 *
	 * @param $query
	 *
	 * @return $this
	 */
	public function subject($query)
	{
		$this->add( "[{$query}]" );

		return $this;
	}

	/**
	 * Filter to get only emails from a specific email address
	 *
	 * @param $email
	 *
	 * @return $this
	 */
	public function from( $email )
	{
		$this->add( "from:{$email}" );

		return $this;
	}

	/**
	 * Filters emails by tag
	 * Example:
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
		$this->add( "in:{$box}" );

		return $this;
	}

	/**
	 * Determines if the email has attachments
	 *
	 * @return $this
	 */
	public function hasAttachment()
	{
		$this->add( 'has:attachment' );

		return $this;
	}

	/**
	 * Preload the information on each Mail objects.
	 * If is not preload you will have to call the load method from the Mail class
	 * @see Mail::load()
	 *
	 * @return $this
	 */
	public function preload()
	{
		$this->preload = true;

		return $this;
	}
}