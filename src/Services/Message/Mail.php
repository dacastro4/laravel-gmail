<?php

namespace Dacastro4\LaravelGmail\Services\Message;

use Carbon\Carbon;
use Dacastro4\LaravelGmail\GmailConnection;
use Dacastro4\LaravelGmail\Traits\HasDecodableBody;
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
	 *
	 */
	public function __construct( \Google_Service_Gmail_Message $message = null, $preload = false )
	{

		$this->service = new Google_Service_Gmail( $this );

		$this->__rConstruct();
		$this->__mConstruct();
		parent::__construct( config() );

		if ( ! is_null( $message ) ) {
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
	 * @return array
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
	 * @return Collection
	 */
	public function getHeaders()
	{
		return $this->buildHeaders( $this->payload->getHeaders() );
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

	/**
	 * Returns array of name and email of each recipient
	 *
	 * @return array
	 */
	public function getFrom()
	{
		$from = $this->getHeader( 'From' );

		preg_match( '/<(.*)>/', $from, $matches );

		$name = preg_replace( '/ <(.*)>/', '', $from );

		return [
			'name'  => $name,
			'email' => isset( $matches[ 1 ] ) ? $matches[ 1 ] : null,
		];
	}

	/**
	 * Returns email of sender
	 *
	 * @return string|null
	 */
	public function getFromEmail()
	{
		$from = $this->getHeader( 'From' );

		preg_match( '/<(.*)>/', $from, $matches );

		return isset( $matches[ 1 ] ) ? $matches[ 1 ] : null;
	}

	/**
	 * Returns name of the sender
	 *
	 * @return string|null
	 */
	public function getFromName()
	{
		$from = $this->getHeader( 'From' );

		$name = preg_replace( '/ <(.*)>/', '', $from );

		return $name;
	}

	/**
	 * Returns array list of recipients
	 *
	 * @return array
	 */
	public function getTo()
	{
		$allTo = $this->getHeader( 'To' );

		return $this->formatEmailList( $allTo );
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
	 * @return string
	 */
	public function getPlainTextBody( $raw = false )
	{
		$content = $this->getBody();

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
		$content = $this->getBody( 'text/html' );

		return $raw ? $content : $this->getDecodedBody( $content );
	}

	/**
	 * @return string base64 version of the body
	 */
	public function getRawHtmlBody()
	{
		return $this->getHtmlBody( true );
	}

	/**
	 * Returns a collection of attachments
	 *
	 * @param bool $preload Preload the attachment's data
	 *
	 * @return Collection
	 * @throws \Exception
	 */
	public function getAttachments( $preload = false )
	{
		$attachments = new Collection( [] );
		$parts = $this->payload->getParts();

		/** @var \Google_Service_Gmail_MessagePart $part */
		foreach ( $parts as $part ) {

			$body = $part->getBody();

			if ( $body->getAttachmentId() ) {
				$attachment = ( new Attachment( $this->getId(), $part ) );
				if ( $preload ) {
					$attachment = $attachment->getData();
				}
				$attachments->push(
					$attachment
				);
			}

		}

		return $attachments;

	}

	/**
	 * @return Collection
	 * @throws \Exception
	 */
	public function getAttachmentsWithData()
	{
		return $this->getAttachments( true );
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
	private function getBodyPart( $type = 'text/plain' )
	{
		$body = $this->payload->getParts();


		if ( $this->hasAttachments() ) {
			//Get the first attachment that is the main body
			$body = isset( $body[ 0 ] ) ? $body[ 0 ] : [];
			$parts = $body->getParts();
		} else {
			$parts = $body;
		}

		/** @var \Google_Service_Gmail_MessagePart $part */
		foreach ( $parts as $part ) {
			if ( $part->getMimeType() === $type ) {
				break;
			}
		}

		return isset($part) ? $part : null;

	}

	public function getBody( $type = 'text/plain' )
	{
		$part = $this->getBodyPart( $type );
		$body = $part->getBody();

		return $body->getData();
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

	private function buildHeaders( $emailHeaders )
	{
		$headers = [];

		foreach ( $emailHeaders as $header ) {
			/** @var \Google_Service_Gmail_MessagePartHeader $header */

			$head = new \stdClass();

			$head->key = $header->getName();
			$head->value = $header->getValue();

			$headers[] = $head;
		}

		return collect( $headers );

	}

	/**
	 * Returns an array of emails from an string in RFC 822 format
	 *
	 * @param string $emails email list in RFC 822 format
	 *
	 * @return array
	 */
	public function formatEmailList( $emails )
	{
		$all = [];
		$explodedEmails = explode( ',', $emails );

		foreach ( $explodedEmails as $email ) {

			$item = [];

			preg_match( '/<(.*)>/', $email, $matches );

			$item[ 'email' ] = str_replace( ' ', '', isset( $matches[ 1 ] ) ? $matches[ 1 ] : $email );

			$name = preg_replace( '/ <(.*)>/', '', $email );

			if ( starts_with( $name, ' ' ) ) {
				$name = substr( $name, 1 );
			}

			$item[ 'name' ] = str_replace( "\"", '', $name ?: null );

			$all[] = $item;

		}

		return $all;
	}

	/**
	 * Sets the access token in case we wanna use a different token
	 *
	 * @param string $token
	 *
	 * @return Mail
	 */
	public function using( $token )
	{
		$this->setToken($token);

		return $this;
	}



	/* added by buckfuddey */

	public function hasNoParts(){
		if (empty($this->payload->getParts())) {
			return true;
		} else {
			return false;
		}
	}


	public function extractFromBody(){

		if ($this->hasNoParts()) {
			$type = $this->payload->getMimeType();
			$body = $this->payload->getBody();
			if ($type == 'text/html' || $type == 'text/plain') {
				$this->bodyArr[$type] = $this->getDecodedBody($body->getData());
			}
			if ($body->getAttachmentId()) {
				$this->attachmentData[] = array(
					'id' => $body->getAttachmentId(),
					'fileName' => $part->getFilename(),
					'mimeType' => $type
				);
			}
		} else {
			$parts = $this->payload->getParts();
			foreach ($parts as $part) {
				if (empty($part->getParts())) {
					$type = $part->getMimeType();
					$body = $part->getBody();
					if ($type == 'text/html' || $type == 'text/plain') {
						if (isset($this->messageBodyArr[$type])) {
							$this->messageBodyArr[$type] .= $this->getDecodedBody($body->getData());
						} else {
							$this->messageBodyArr[$type] = $this->getDecodedBody($body->getData());
						}
					}

					if ($body->getAttachmentId()) {
						$this->attachmentData[] = array(
							'id' => $body->getAttachmentId(),
							'fileName'  => $part->getFilename(),
							'mimeType' => $type
						);						
					}				
				} else {
					$subParts = $part->getParts();
					$this->traverseData($subParts);
				}
			}
		}		
	}

	public function traverseData($parts){
		foreach ($parts as $part) {			
			if (empty($part->getParts())) {
				$type = $part->getMimeType();
				$body = $part->getBody();
				if ($type == 'text/html' || $type == 'text/plain') {
					if (isset($this->messageBodyArr[$type])) {
						$this->messageBodyArr[$type] .= $this->getDecodedBody($body->getData());
					} else {
						$this->messageBodyArr[$type] = $this->getDecodedBody($body->getData());
					}						
				}

				if ($body->getAttachmentId()) {
					$this->attachmentData[] = array(
						'id' => $body->getAttachmentId(),
						'fileName'  => $part->getFilename(),
						'mimeType' => $type
					);

				}
			} else {
				$subParts = $part->getParts();
				$this->traverseData($subParts);
			}
		}
	}

	public function getDecodedBody( $content ) {
		$content = str_replace( '_', '/', str_replace( '-', '+', $content ) );
		return base64_decode( $content );
	}

}
