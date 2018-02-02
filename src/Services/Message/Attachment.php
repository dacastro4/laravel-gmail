<?php

namespace Dacastro4\LaravelGmail\Services\Message;

use Dacastro4\LaravelGmail\GmailConnection;
use Dacastro4\LaravelGmail\Traits\HasDecodableBody;
use Google_Service_Gmail;
use Illuminate\Support\Facades\Storage;

class Attachment extends GmailConnection
{
	use HasDecodableBody;

	public $body;
	public $id;
	public $filename;
	public $mimeType;
	public $size;
	private $service;

	/**
	 * @var \Google_Service_Gmail_MessagePart
	 */
	private $part;
	/**
	 * @var \Google_Service_Gmail
	 */
	private $client = null;
	private $messageId;

	public function __construct( $singleMessageId, \Google_Service_Gmail_MessagePart $part )
	{
		parent::__construct();

		$this->service = new Google_Service_Gmail( $this );

		$body = $part->getBody();
		$this->id = $body->getAttachmentId();
		$this->size = $body->getAttachmentId();
		$this->filename = $part->getFilename();
		$this->mimeType = $part->getMimeType();
		$this->messageId = $singleMessageId;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getFileName()
	{
		return $this->filename;
	}

	public function getMimeType()
	{
		return $this->getMimeType();
	}

	public function getSize()
	{
		return $this->size;
	}

	/**
	 * @throws \Exception
	 */
	public function getData()
	{
		$attachment = $this->service->users_messages_attachments->get( 'me', $this->messageId, $this->id );

		return $attachment->getData();
	}

	/**
	 * @param      $path
	 * @param null $filename
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function saveAttachmentTo( $path = null, $filename = null )
	{

		$data = $this->getDecodedBody($this->getData());

		if(!$data) {
			throw new \Exception('Could not get the attachment');
		}

		$filename = $filename ?? $this->filename;

		if($path) {
			if(!ends_with('/', $path)) {
				$path = "{$path}/";
			}
		} else {
			$path = '/';
		}

		$filePathAndName = "{$path}{$filename}";

		Storage::put($filePathAndName, $data);

		return $filePathAndName;

	}
}