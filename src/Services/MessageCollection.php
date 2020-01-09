<?php

namespace Dacastro4\LaravelGmail\Services;

use Illuminate\Support\Collection;

class MessageCollection extends Collection
{
	/**
	 * @var Message
	 */
	private $message;

	/**
	 * MessageCollection constructor.
	 *
	 * @param Message $message
	 * @param array $items
	 */
	public function __construct( $items = [], Message $message = null )
	{
		parent::__construct( $items );
		$this->message = $message;
	}

	public function next()
	{
		return $this->message->next();
	}

	/**
	 * Returns boolean if the page token variable is null or not
	 *
	 * @return bool
	 */
	public function hasNextPage()
	{
		return !!$this->message->pageToken;
	}
}
