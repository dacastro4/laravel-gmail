<?php

namespace Dacastro4\LaravelGmail\Traits;

use Dacastro4\LaravelGmail\Services\Message;

trait Filterable
{
	/**
	 * Filter to get only unread emalis
	 *
	 * @return self|Message
	 */
	public function unread()
	{
		$this->add( 'is:unread' );

		return $this;
	}

	public abstract function add( $query, $column = 'q', $encode = true );

	/**
	 * Filter to get only unread emalis
	 *
	 * @param $query
	 *
	 * @return self|Message
	 */
	public function subject( $query )
	{
		$this->add( "[{$query}]" );

		return $this;
	}

	/**
	 * Filter to get only emails from a specific email address
	 *
	 * @param $email
	 *
	 * @return self|Message
	 */
	public function to( $email )
	{
		$this->add( "to:{$email}" );

		return $this;
	}

	/**
	 * add an array of from addresses
	 *
	 * @param $emails
	 *
	 * @return self|Message
	 */
	public function fromThese( array $emails )
	{
		$emailsCount = count( $emails );
		for ( $i = 0; $i < $emailsCount; $i ++ ) {
			! $i ? $this->add( "{from:$emails[$i]" ) : $i == $emailsCount - 1 ? $this->add( "from:$emails[$i]}" ) : $this->from( $emails[ $i ] );
		}

		return $this;
	}

	/**
	 * Filter to get only emails from a specific email address
	 *
	 * @param $email
	 *
	 * @return self|Message
	 */
	public function from( $email )
	{
		$this->add( "from:{$email}" );

		return $this;
	}

	/**
	 * Filter to get only emails after a specific date
	 *
	 * @param $date
	 *
	 * @return self|Message
	 */
	public function after( $date )
	{
		$this->add( "after:{$date}" );

		return $this;
	}

	/**
	 * Filter to get only emails before a specific date
	 *
	 * @param $date
	 *
	 * @return self|Message
	 */
	public function before( $date )
	{
		$this->add( "before:{$date}" );

		return $this;
	}

	/**
	 * Filter by a Gmail raw query
	 * Label should be the last thing to put in the raw query
	 *
	 * @param $query
	 *
	 * @return self|Message
	 */
	public function raw( $query )
	{
		$this->add( $query, 'q', false );

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
	 * @return self|Message
	 */
	public function in( $box = 'inbox' )
	{
		$this->add( "in:{$box}" );

		return $this;
	}

	/**
	 * Determines if the email has attachments
	 *
	 * @return self|Message
	 */
	public function hasAttachment()
	{
		$this->add( 'has:attachment' );

		return $this;
	}
}
