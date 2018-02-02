<?php

namespace Dacastro4\LaravelGmail\Services\Message;

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
		Replyable;

	public $id;
	public $internalDate;
	public $labels;
	public $size;
	public $threatId;
	public $payload;
	public $service;

	/**
	 * SingleMessage constructor.
	 *
	 * @param \Google_Service_Gmail_Message $message
	 * @param bool                          $preload
	 */
	public function __construct( \Google_Service_Gmail_Message $message, bool $preload = false)
	{

		$this->service = new Google_Service_Gmail( $this );

		parent::__construct();

		if($preload) {
			$message = $this->service->users_messages->get('me', $message->getId());
		}

		$this->id = $message->getId();
		$this->internalDate = $message->getInternalDate();
		$this->labels = $message->getLabelIds();
		$this->size = $message->getSizeEstimate();
		$this->threatId = $message->getThreadId();
		$this->payload = $message->getPayload();
	}

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return int UNIX date
	 */
	public function getInternalDate()
	{
		return $this->internalDate;
	}

	/**
	 * @return mixed
	 */
	public function getLabels()
	{
		return $this->labels;
	}

	/**
	 * @return mixed
	 */
	public function getSize()
	{
		return $this->size;
	}

	/**
	 * @return string
	 */
	public function getThreatId()
	{
		return $this->threatId;
	}

	/**
	 * @return \Google_Service_Gmail_MessagePartHeader
	 */
	public function getHeaders()
	{
		return $this->payload->getHeaders();
	}

	/**
	 * @param bool $raw
	 *
	 * @return bool|string
	 */
	public function getPlainTextBody( $raw = false)
	{
		$part = $this->getBodyPart();
		$body = $part->getBody();
		$content = $body->getData();

		return $raw ? $content : $this->getDecodedBody($content);
	}

	/**
	 * @return string base64 version of the body
	 */
	public function getRawPlainTextBody()
	{
		return $this->getPlainTextBody(true);
	}

	/**
	 * @param bool $raw
	 *
	 * @return string
	 */
	public function getHtmlBody( $raw = false)
	{
		$part = $this->getBodyPart('text/html');
		$body = $part->getBody();
		$content = $body->getData();

		return $raw ? $content : $this->getDecodedBody($content);
	}

	/**
	 * @return string base64 version of the body
	 */
	public function getRawHtmlBody()
	{
		return $this->getHtmlBody(true);
	}

	public function getAttachments()
	{
		$attachments = new Collection([]);
		$parts = $this->payload->getParts();

		/**
		 * @var \Google_Service_Gmail_MessagePart $part
		 */
		foreach ($parts as $part) {

			$body = $part->getBody();

			if($body->getAttachmentId()) {
				$attachment = (new Attachment($this->getId(), $part));
				$attachments->push(
					$attachment
				);
			}

		}

		return $attachments;

	}

	/**
	 * @return int
	 */
	public function hasAttachments()
	{
		$attachments = 0;
		$parts = $this->payload->getParts();

		/**
		 * @var \Google_Service_Gmail_MessagePart $part
		 */
		foreach ($parts as $part) {
			$body = $part->getBody();
			if($body->getAttachmentId()) {
				$attachments++;
			}
		}

		return !!$attachments;
	}

	/**
	 * @param string $type
	 *
	 * @return \Google_Service_Gmail_MessagePart|null
	 */
	private function getBodyPart( $type = 'text/plain' )
	{
		$body = $this->payload->getParts();

		if(isset($body[0])) {
			$body = $body[0];

			$parts = $body->getParts();

			/**
			 * @var \Google_Service_Gmail_MessagePart $part
			 */
			foreach ($parts as $part) {
				if($part->getMimeType() === $type) {
					break;
				}
			}

			return $part ?? null;

		}

		return null;
	}

	public function load()
	{
		$message = $this->service->users_messages->get('me', $this->getId());

		return new self($message);
	}

	/**
	 * @return \Google_Service_Gmail_MessagePart
	 */
	private function getPayload()
	{
		return $this->payload;
	}
}