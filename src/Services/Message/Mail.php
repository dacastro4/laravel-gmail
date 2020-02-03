<?php

namespace Dacastro4\LaravelGmail\Services\Message;

use Carbon\Carbon;
use Dacastro4\LaravelGmail\GmailConnection;
use Dacastro4\LaravelGmail\Traits\HasDecodableBody;
use Dacastro4\LaravelGmail\Traits\HasParts;
use Dacastro4\LaravelGmail\Traits\Modifiable;
use Dacastro4\LaravelGmail\Traits\Replyable;
use Google_Service_Gmail;
use Google_Service_Gmail_MessagePart;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Class SingleMessage
 *
 * @package Dacastro4\LaravelGmail\services
 */
class Mail extends GmailConnection
{

	use HasDecodableBody,
		Modifiable,
		HasParts,
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
	public $threadId;

	/**
	 * @var \Google_Service_Gmail_MessagePart
	 */
	public $payload;

	public $parts;

	/**
	 * @var Google_Service_Gmail
	 */
	public $service;

	/**
	 * SingleMessage constructor.
	 *
	 * @param \Google_Service_Gmail_Message $message
	 * @param bool $preload
	 * @param  int 	$userId
	 */
	public function __construct(\Google_Service_Gmail_Message $message = null, $preload = false, $userId = null)
	{
		$this->service = new Google_Service_Gmail($this);

		$this->__rConstruct();
		$this->__mConstruct();
		parent::__construct(config(), $userId);

		if (!is_null($message)) {
			if ($preload) {
				$message = $this->service->users_messages->get('me', $message->getId());
			}

			$this->setMessage($message);

			if ($preload) {
				$this->setMetadata();
			}
		}
	}

	/**
	 * Sets data from mail
	 *
	 * @param \Google_Service_Gmail_Message $message
	 */
	protected function setMessage(\Google_Service_Gmail_Message $message)
	{
		$this->id = $message->getId();
		$this->internalDate = $message->getInternalDate();
		$this->labels = $message->getLabelIds();
		$this->size = $message->getSizeEstimate();
		$this->threadId = $message->getThreadId();
		$this->payload = $message->getPayload();
		if ($this->payload) {
			$this->parts = collect($this->payload->getParts());
		}
	}

	/**
	 * Sets the metadata from Mail when preloaded
	 */
	protected function setMetadata()
	{
		$this->to = $this->getTo();
		$from = $this->getFrom();
		$this->from = isset($from['email']) ? $from['email'] : null;
		$this->nameFrom = isset($from['email']) ? $from['email'] : null;

		$this->subject = $this->getSubject();
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
	 * Returns thread ID of the email
	 *
	 * @return string
	 */
	public function getThreadId()
	{
		return $this->threadId;
	}

	/**
	 * Returns all the headers of the email
	 *
	 * @return Collection
	 */
	public function getHeaders()
	{
		return $this->buildHeaders($this->payload->getHeaders());
	}

	/**
	 * Returns the subject of the email
	 *
	 * @return string
	 */
	public function getSubject()
	{
		return $this->getHeader('Subject');
	}

	/**
	 * Returns the subject of the email
	 *
	 * @return array|string
	 */
	public function getReplyTo()
	{
		$replyTo = $this->getHeader('Reply-To');

		return $this->getFrom($replyTo ? $replyTo : $this->getHeader('From'));
	}

	/**
	 * Returns array of name and email of each recipient
	 *
	 * @param string|null $email
	 * @return array
	 */
	public function getFrom($email = null)
	{
		$from = $email ? $email : $this->getHeader('From');

		preg_match('/<(.*)>/', $from, $matches);

		$name = preg_replace('/ <(.*)>/', '', $from);

		return [
			'name'  => $name,
			'email' => isset($matches[1]) ? $matches[1] : null,
		];
	}

	/**
	 * Returns email of sender
	 *
	 * @return string|null
	 */
	public function getFromEmail()
	{
		$from = $this->getHeader('From');

		if (filter_var($from, FILTER_VALIDATE_EMAIL)) {
			return $from;
		}

		preg_match('/<(.*)>/', $from, $matches);

		return isset($matches[1]) ? $matches[1] : null;
	}

	/**
	 * Returns name of the sender
	 *
	 * @return string|null
	 */
	public function getFromName()
	{
		$from = $this->getHeader('From');

		$name = preg_replace('/ <(.*)>/', '', $from);

		return $name;
	}

	/**
	 * Returns array list of recipients
	 *
	 * @return array
	 */
	public function getTo()
	{
		$allTo = $this->getHeader('To');

		return $this->formatEmailList($allTo);
	}

	/**
	 * Returns array list of cc recipients
	 *
	 * @return array
	 */
	public function getCc()
	{
		$allCc = $this->getHeader('Cc');

		return $this->formatEmailList($allCc);
	}

	/**
	 * Returns array list of bcc recipients
	 *
	 * @return array
	 */
	public function getBcc()
	{
		$allBcc = $this->getHeader('Bcc');

		return $this->formatEmailList($allBcc);
	}

	/**
	 * Returns an array of emails from an string in RFC 822 format
	 *
	 * @param string $emails email list in RFC 822 format
	 *
	 * @return array
	 */
	public function formatEmailList($emails)
	{
		$all = [];
		$explodedEmails = explode(',', $emails);

		foreach ($explodedEmails as $email) {

			$item = [];

			preg_match('/<(.*)>/', $email, $matches);

			$item['email'] = str_replace(' ', '', isset($matches[1]) ? $matches[1] : $email);

			$name = preg_replace('/ <(.*)>/', '', $email);

			if (Str::startsWith($name, ' ')) {
				$name = substr($name, 1);
			}

			$item['name'] = str_replace("\"", '', $name ?: null);

			$all[] = $item;

		}

		return $all;
	}

	/**
	 * Returns the original date that the email was sent
	 *
	 * @return Carbon
	 */
	public function getDate()
	{
		return Carbon::parse($this->getHeader('Date'));
	}

	/**
	 * Returns email of the original recipient
	 *
	 * @return string
	 */
	public function getDeliveredTo()
	{
		return $this->getHeader('Delivered-To');
	}

	/**
	 * Base64 version of the body
	 *
	 * @return string
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
	public function getPlainTextBody($raw = false)
	{
		$content = $this->getBody();

		return $raw ? $content : $this->getDecodedBody($content);
	}

	/**
	 * Returns a specific body part from an email
	 *
	 * @param string $type
	 *
	 * @return null|string
	 * @throws \Exception
	 */
	public function getBody($type = 'text/plain')
	{
		$parts = $this->getAllParts($this->parts);

		try {
			if (!$parts->isEmpty()) {
				foreach ($parts as $part) {
					if ($part->mimeType == $type) {
						return $part->body->data;
						//if there are no parts in payload, try to get data from body->data
					} elseif ($this->payload->body->data) {
						return $this->payload->body->data;
					}
				}
			} else {
				return $this->payload->body->data;
			}
		} catch (\Exception $exception) {
			throw new \Exception("Preload or load the single message before getting the body.");
		}

		return null;
	}

	/**
	 * True if message has at least one attachment.
	 *
	 * @return boolean
	 */
	public function hasAttachments()
	{
		$parts = $this->getAllParts($this->parts);
		$has = false;

		/** @var Google_Service_Gmail_MessagePart $part */
		foreach ($parts as $part) {
			if (!empty($part->body->attachmentId) && $part->getFilename() != null && strlen($part->getFilename()) > 0) {
				$has = true;
				break;
			}
		}

		return $has;
	}

	/**
	 * Number of attachments of the message.
	 *
	 * @return int
	 */
	public function countAttachments()
	{
		$numberOfAttachments = 0;
		$parts = $this->getAllParts($this->parts);

		foreach ($parts as $part) {
			if (!empty($part->body->attachmentId)) {
				$numberOfAttachments++;
			}
		}

		return $numberOfAttachments;
	}

	/**
	 * Decodes the body from gmail to make it readable
	 *
	 * @param $content
	 * @return bool|string
	 */
	public function getDecodedBody($content)
	{
		$content = str_replace('_', '/', str_replace('-', '+', $content));

		return base64_decode($content);
	}

	/**
	 * @return string base64 version of the body
	 */
	public function getRawHtmlBody()
	{
		return $this->getHtmlBody(true);
	}

	/**
	 * Gets the HTML body
	 *
	 * @param bool $raw
	 *
	 * @return string
	 */
	public function getHtmlBody($raw = false)
	{
		$content = $this->getBody('text/html');

		return $raw ? $content : $this->getDecodedBody($content);
	}

	/**
	 * Get a collection of attachments with full information
	 *
	 * @return Collection
	 * @throws \Exception
	 */
	public function getAttachmentsWithData()
	{
		return $this->getAttachments(true);
	}

	/**
	 * Returns a collection of attachments
	 *
	 * @param bool $preload Preload only the attachment's 'data'.
	 * But does not load the other attachment info like filename, mimetype, etc..
	 *
	 * @return Collection
	 * @throws \Exception
	 */
	public function getAttachments($preload = false)
	{
		$attachments = new Collection();
		$parts = $this->getAllParts($this->parts);

		foreach ($parts as $part) {
			if (!empty($part->body->attachmentId)) {
				$attachment = (new Attachment($part->body->attachmentId, $part));

				if ($preload) {
					$attachment = $attachment->getData();
				}

				$attachments->push($attachment);
			}
		}

		return $attachments;
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
	 * Gets the user email from the config file
	 *
	 * @return mixed|null
	 */
	public function getUser()
	{
		return $this->config('email');
	}

	/**
	 * Get's the gmail information from the Mail
	 *
	 * @return Mail
	 */
	public function load()
	{
		$message = $this->service->users_messages->get('me', $this->getId());

		return new self($message);
	}

	/**
	 * Sets the access token in case we wanna use a different token
	 *
	 * @param string $token
	 *
	 * @return Mail
	 */
	public function using($token)
	{
		$this->setToken($token);

		return $this;
	}

	/**
	 * checks if message has at least one part without iterating through all parts
	 *
	 * @return bool
	 */
	public function hasParts()
	{
		return !!$this->iterateParts($this->parts, $returnOnFirstFound = true);
	}

	/**
	 * Gets all the headers from an email and returns a collections
	 *
	 * @param $emailHeaders
	 * @return Collection
	 */
	private function buildHeaders($emailHeaders)
	{
		$headers = [];

		foreach ($emailHeaders as $header) {
			/** @var \Google_Service_Gmail_MessagePartHeader $header */

			$head = new \stdClass();

			$head->key = $header->getName();
			$head->value = $header->getValue();

			$headers[] = $head;
		}

		return collect($headers);
	}
}
