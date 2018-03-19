<?php

namespace Dacastro4\LaravelGmail\Traits;

use Dacastro4\LaravelGmail\Services\Message\Mail;
use Google_Service_Gmail;

/**
 * @property Google_Service_Gmail $service
 */
trait Modifiable
{

	use ModifiesLabels {
		ModifiesLabels::__construct as private __mlConstruct;
	}

	private $messageRequest;

	public function __construct()
	{
		/** @scrutinizer ignore-call */
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
			return $this->removeLabel( 'UNREAD' );
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
			return $this->addLabel( 'UNREAD' );
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
			return $this->addLabel( 'IMPORTANT' );
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
			return $this->removeLabel( 'IMPORTANT' );
		} catch ( \Exception $e ) {
			throw new \Exception( "Couldn't mark email as unread: {$e->getMessage()}" );
		}
	}

	/**
	 * @return Mail|string
	 * @throws \Exception
	 */
	public function addStar()
	{
		try {
			return $this->addLabel( 'STARRED' );
		} catch ( \Exception $e ) {
			throw new \Exception( "Couldn't remove mark email as important.: {$e->getMessage()}" );
		}
	}

	/**
	 * @return Mail|string
	 * @throws \Exception
	 */
	public function removeStar()
	{
		try {
			return $this->removeLabel( 'STARRED' );
		} catch ( \Exception $e ) {
			throw new \Exception( "Couldn't mark email as unread: {$e->getMessage()}" );
		}
	}

	/**
	 * Send the email to the trash
	 *
	 * @return \Dacastro4\LaravelGmail\Services\Message\Mail|\Exception
	 */
	public function sendToTrash()
	{
		try {
			return $this->addLabel( 'TRASH' );
		} catch ( \Exception $e ) {
			return new \Exception( "Couldn't mark email as trash: {$e->getMessage()}" );
		}
	}

	public function removeFromTrash()
	{
		try {
			return $this->removeLabel( 'TRASH' );
		} catch ( \Exception $e ) {
			return new \Exception( "Couldn't untrash the email: {$e->getMessage()}" );
		}
	}
}