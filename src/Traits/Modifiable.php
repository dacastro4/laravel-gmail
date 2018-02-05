<?php

namespace Dacastro4\LaravelGmail\Traits;

use Dacastro4\LaravelGmail\Services\Message\Mail;
use Google_Service_Gmail;
use Google_Service_Gmail_ModifyMessageRequest;

/**
 * @property Google_Service_Gmail $service
 */
trait Modifiable
{

	//TODO search for a naming convention for all the methods

	use ModifiesLabels {
		ModifiesLabels::__construct as private __mlConstruct();
	}

	private $messageRequest;

	public function __construct()
	{
		$this->__mlConstruct();
	}

	/**
	 * Marks emails as "READ". Returns string of message if fail
	 *
	 * @return Mail|string
	 */
	public function markAsRead()
	{
		try {
			return $this->removeSingleLabel( 'UNREAD' );
		} catch ( \Exception $e ) {
			return "Couldn't mark email as read: {$e->getMessage()}";
		}
	}

	/**
	 * Marks emails as unread
	 *
	 * @return Mail|string
	 * @throws \Exception
	 */
	public function markAsUnread()
	{
		try {
			return $this->addSingleLabel( 'UNREAD' );
		} catch ( \Exception $e ) {
			throw new \Exception( "Couldn't mark email as unread: {$e->getMessage()}" );
		}
	}

	/**
	 * @return Mail|string
	 * @throws \Exception
	 */
	public function markAsImportant()
	{
		try {
			return $this->addSingleLabel( 'IMPORTANT' );
		} catch ( \Exception $e ) {
			throw new \Exception( "Couldn't remove mark email as important.: {$e->getMessage()}" );
		}
	}

	/**
	 * @return Mail|string
	 * @throws \Exception
	 */
	public function markAsNotImportant()
	{
		try {
			return $this->removeSingleLabel( 'IMPORTANT' );
		} catch ( \Exception $e ) {
			throw new \Exception( "Couldn't mark email as unread: {$e->getMessage()}" );
		}
	}

	/**
	 * @return Mail|string
	 * @throws \Exception
	 */
	public function star()
	{
		try {
			return $this->addSingleLabel( 'STARRED' );
		} catch ( \Exception $e ) {
			throw new \Exception( "Couldn't remove mark email as important.: {$e->getMessage()}" );
		}
	}

	/**
	 * @return Mail|string
	 * @throws \Exception
	 */
	public function unStart()
	{
		try {
			return $this->removeSingleLabel( 'STARRED' );
		} catch ( \Exception $e ) {
			throw new \Exception( "Couldn't mark email as unread: {$e->getMessage()}" );
		}
	}

	/**
	 * Send the email to the trash
	 *
	 * @return \Dacastro4\LaravelGmail\Services\Message\Mail|\Exception
	 */
	public function trash()
	{
		try {
			return $this->addSingleLabel( 'TRASH' );
		} catch ( \Exception $e ) {
			return new \Exception( "Couldn't mark email as trash: {$e->getMessage()}" );
		}
	}

	public function untrash()
	{
		try {
			return $this->removeSingleLabel( 'TRASH' );
		} catch ( \Exception $e ) {
			return new \Exception( "Couldn't untrash the email: {$e->getMessage()}" );
		}
	}
}