<?php

namespace Dacastro4\LaravelGmail\Services\Message;

use Carbon\Carbon;
use Dacastro4\LaravelGmail\GmailConnection;
use Dacastro4\LaravelGmail\Traits\HasDecodableBody;
use Dacastro4\LaravelGmail\Traits\HasHeaders;
use Dacastro4\LaravelGmail\Traits\Modifiable;
use Dacastro4\LaravelGmail\Traits\Replyable;
use Google_Service_Gmail;
use Illuminate\Support\Collection;

/**
 * Class SingleMessage
 * @package Dacastro4\LaravelGmail\services
 */
class Mail extends GmailConnection
{
	use HasDecodableBody,
		HasHeaders,
		Modifiable,
		Replyable {
		Replyable::__construct as private __rConstruct;
		Modifiable::__construct as private __mConstruct;
	}

	/**
	 * @var
	 */
	public $id;

	/**
	 * @var
	 */
	public $internalDate;

	/**
	 * @var
	 */
	public $labels;

	/**
	 * @var
	 */
	public $size;

	/**
	 * @var
	 */
	public $threatId;

	/**
	 * @var \Google_Service_Gmail_MessagePart
	 */
	public $payload;

	/**
	 * @var Google_Service_Gmail
	 */
	public $service;

	/**
	 * SingleMessage constructor.
	 *
	 * @param \Google_Service_Gmail_Message $message
	 * @param bool $preload
	 */
	public function __construct( \Google_Service_Gmail_Message $message, $preload = false )
	{

		$this->service = new Google_Service_Gmail( $this );

		$this->__rConstruct();
		$this->__mConstruct();
		parent::__construct();

		if ( $preload ) {
			$message = $this->service->users_messages->get( 'me', $message->getId() );
		}

		$this->id = $message->getId();
		$this->internalDate = $message->getInternalDate();
		$this->labels = $message->getLabelIds();
		$this->size = $message->getSizeEstimate();
		$this->threatId = $message->getThreadId();
		$this->payload = $message->getPayload();
	}

	/**
	 * Returns ID of the email
	 *
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Return a UNIX version of the date
	 *
	 * @return int UNIX date
	 */
	public function getInternalDate()
	{
		return $this->internalDate;
	}

	/**
	 * Returns the labels of the email
	 * Example: INBOX, STARRED, UNREAD
	 *
	 * @return mixed
	 */
	public function getLabels()
	{
		return $this->labels;
	}

	/**
	 * Returns approximate size of the email
	 *
	 * @return mixed
	 */
	public function getSize()
	{
		return $this->size;
	}

	/**
	 * Returns threat ID of the email
	 *
	 * @return string
	 */
	public function getThreatId()
	{
		return $this->threatId;
	}

	/**
	 * Returns all the headers of the email
	 *
	 * @return \Google_Service_Gmail_MessagePartHeader
	 */
	public function getHeaders()
	{
		return $this->payload->getHeaders();
	}

	/**
	 * Returns the subject of the email
	 *
	 * @return string
	 */
	public function getSubject()
	{
		return $this->getHeader( 'Subject' );
	}

	public function getFrom()
	{

		//TODO: Fix to work with multiple emails
		$email = $this->getHeader( 'From' );

		preg_match( '/<(.*)>/', $email, $matches );

		return isset( $matches[ 1 ] ) ? $matches[ 1 ] : null;
	}

	/**
	 * Returns the original date that the email was sent
	 *
	 * @return Carbon
	 */
	public function getDate()
	{
		return Carbon::parse( $this->getHeader( 'Date' ) );
	}

	/**
	 * Returns email of the original recipient
	 *
	 * @return string
	 */
	public function getDeliveredTo()
	{
		return $this->getHeader( 'Delivered-To' );
	}

	/**
	 * @param bool $raw
	 *
	 * @return bool|string
	 */
	public function getPlainTextBody( $raw = false )
	{
		$part = $this->getBodyPart();
		$body = $part->getBody();
		$content = $body->getData();

		return $raw ? $content : $this->getDecodedBody( $content );
	}

	/**
	 * @return string base64 version of the body
	 */
	public function getRawPlainTextBody()
	{
		return $this->getPlainTextBody( true );
	}

	/**
	 * @param bool $raw
	 *
	 * @return string
	 */
	public function getHtmlBody( $raw = false )
	{
		$part = $this->getBodyPart( 'text/html' );
		$body = $part->getBody();
		$content = $body->getData();

		return $raw ? $content : $this->getDecodedBody( $content );
	}

	/**
	 * @return string base64 version of the body
	 */
	public function getRawHtmlBody()
	{
		return $this->getHtmlBody( true );
	}

	public function getAttachments()
	{
		$attachments = new Collection( [] );
		$parts = $this->payload->getParts();

		/** @var \Google_Service_Gmail_MessagePart $part */
		foreach ( $parts as $part ) {

			$body = $part->getBody();

			if ( $body->getAttachmentId() ) {
				$attachment = ( new Attachment( $this->getId(), $part ) );
				$attachments->push(
					$attachment
				);
			}

		}

		return $attachments;

	}

	/**
	 * @return boolean
	 */
	public function hasAttachments()
	{
		$attachments = 0;
		$parts = $this->payload->getParts();

		/**  @var \Google_Service_Gmail_MessagePart $part */
		foreach ( $parts as $part ) {
			$body = $part->getBody();
			if ( $body->getAttachmentId() ) {
				$attachments ++;
				break;
			}
		}

		return ! ! $attachments;
	}

	/**
	 * @param string $type
	 *
	 * @return \Google_Service_Gmail_MessagePart|null
	 */
	private function getBodyPart( $type = 'text/html' )
	{
		$body = $this->payload->getParts();

		if ( isset( $body[ 0 ] ) ) {

			$parts = $body[ 0 ]->getParts();

			/** @var \Google_Service_Gmail_MessagePart $part */
			foreach ( $parts as $part ) {
				if ( $part->getMimeType() === $type ) {
					break;
				}
			}

			return $part ?: null;

		}

		return null;
	}

	/**
	 * Get's the gmail information from the Mail
	 *
	 * @return Mail
	 */
	public function load()
	{
		$message = $this->service->users_messages->get( 'me', $this->getId() );

		return new self( $message );
	}
}