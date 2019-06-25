<?php

namespace Dacastro4\LaravelGmail\Services\Message;

use Carbon\Carbon;
use Dacastro4\LaravelGmail\GmailConnection;
use Dacastro4\LaravelGmail\Traits\HasDecodableBody;
use Dacastro4\LaravelGmail\Traits\HasParts;
use Dacastro4\LaravelGmail\Traits\Modifiable;
use Dacastro4\LaravelGmail\Traits\Replyable;
use Google_Service_Gmail;
use Illuminate\Support\Collection;

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

	/**
	 * @var Google_Service_Gmail
	 */
	public $service;

    /**
     * @var
     */
    private $allParts;

    /**
     * SingleMessage constructor.
     *
     * @param  \Google_Service_Gmail_Message  $message
     * @param  bool  $preload
     * @param  bool  $setMessage
     */
    public function __construct(\Google_Service_Gmail_Message $message = null, $preload = false) {

        $this->service = new Google_Service_Gmail($this);

		$this->__rConstruct();
		$this->__mConstruct();
		parent::__construct(config());

		if (!is_null($message)) {
			if ($preload) {
				$message = $this->service->users_messages->get('me', $message->getId());
			}

            $this->setMessage($message);

			if($preload) {
				$this->setMetadata();
			}
		}
	}

    protected function setMessage(\Google_Service_Gmail_Message $message)
    {
        $this->id = $message->getId();
        $this->internalDate = $message->getInternalDate();
        $this->labels = $message->getLabelIds();
        $this->size = $message->getSizeEstimate();
        $this->threadId = $message->getThreadId();
        $this->payload = $message->getPayload();
    }

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
	 * @param  null  $email
     * @return array
     */
    public function getFrom($email = null)
    {
        $from = $email ? $email : $this->getHeader('From');

        preg_match('/<(.*)>/', $from, $matches);

        $name = preg_replace('/ <(.*)>/', '', $from);

        return [
            'name' => $name,
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
     * Returns an array of emails from an string in RFC 822 format
     *
     * @param  string  $emails  email list in RFC 822 format
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

            if (starts_with($name, ' ')) {
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
     * @return string base64 version of the body
     */
    public function getRawPlainTextBody()
    {
        return $this->getPlainTextBody(true);
    }

    /**
     * @param  bool  $raw
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
     * @param  string  $type
     *
     * @return null|string
     */
    public function getBody($type = 'text/plain')
    {
        $parts = $this->getAllParts(collect([$this->payload]));
        if(!$parts->isEmpty())
        {
            foreach ($parts as $part)
            {
                if ($part->mimeType == $type)
                {
                    return $part->body->data;
                    //if there are no parts in payload, try to get data from body->data
                } elseif ($this->payload->body->data)
                {
                    return $this->payload->body->data;
                }
            }
        }else{
            return $this->payload->body->data;
        }
        return null;

    }

    /**
     * @return boolean. True if message has at least one attachment.
     */
    public function hasAttachments()
    {
        $parts = $this->getAllParts(collect([$this->payload]));
        foreach ($parts as $part) {
            if (!empty($part->body->attachmentId)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return Integer. Number of attachments of the message.
     */
    public function countAttachments()
    {
        $numberOfAttachments = 0;
        $parts = $this->getAllParts(collect([$this->payload]));
        foreach ($parts as $part) {
            if (!empty($part->body->attachmentId)) {
                $numberOfAttachments++;
            }
        }
        return $numberOfAttachments;
    }


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
     * @param  bool  $raw
     *
     * @return string
     */
    public function getHtmlBody($raw = false)
    {
        $content = $this->getBody('text/html');

        return $raw ? $content : $this->getDecodedBody($content);
    }

    /**
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
     * @param  bool  $preload  Preload only the attachment's 'data'.
     * But does not load the other attachment info like filename, mimetype,
     *   etc.. Maybe would be better to push the data to the other info? So ot
     *   would all be together.
     *
     * @return Collection
     * @throws \Exception
     */
    public function getAttachments($preload = false)
    {
        $attachments = new Collection([]);
        $parts = $this->getAllParts(collect([$this->payload]));
        foreach ($parts as $part) {
            if (!empty($part->body->attachmentId)) {
                $attachment = (new Attachment($part->body->attachmentId,
                    $part));
                if ($preload) {
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
     * Returns ID of the email
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }


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
     * @param  string  $token
     *
     * @return Mail
     */
    public function using($token)
    {
        $this->setToken($token);

        return $this;
    }


    /**
     * checks if message has at least one part without itarating through all
     * parts
     *
     * @return bool
     */
    public function hasParts()
    {
        if ($this->iterateParts(
            collect([$this->payload]), $returnOnFirstFound = true)
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * checks if message has no parts withour iterating through all parts
     *
     * @return bool
     */
    public function hasNoParts()
    {
        if ($this->hasParts()) {
            return false;
        } else {
            return true;
        }
    }


    //unused function? Could be removed?
    public function extractFromBody()
    {

        if ($this->hasNoParts()) {
            $type = $this->payload->getMimeType();
            $body = $this->payload->getBody();
            if ($type == 'text/html' || $type == 'text/plain') {
                $this->bodyArr[$type] = $this->getDecodedBody($body->getData());
            }
            if ($body->getAttachmentId()) {
                $this->attachmentData[] = [
                    'id' => $body->getAttachmentId(),
                    'mimeType' => $type,
                ];
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
                        $this->attachmentData[] = [
                            'id' => $body->getAttachmentId(),
                            'fileName' => $part->getFilename(),
                            'mimeType' => $type,
                        ];
                    }
                } else {
                    $subParts = $part->getParts();
                    $this->traverseData($subParts);
                }
            }
        }
    }


    //unused function? Could be removed?
    public function traverseData($parts)
    {
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
                    $this->attachmentData[] = [
                        'id' => $body->getAttachmentId(),
                        'fileName' => $part->getFilename(),
                        'mimeType' => $type,
                    ];

                }
            } else {
                $subParts = $part->getParts();
                $this->traverseData($subParts);
            }
        }
    }
}
