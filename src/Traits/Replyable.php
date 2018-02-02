<?php

namespace Dacastro4\LaravelGmail\Traits;

use Dacastro4\LaravelGmail\Services\Message\Mail;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;
use Swift_Message;

/**
 * @property Google_Service_Gmail $service
 */
trait Replyable
{

	private $swiftMessage;
	private $parameters;
	private $message;
	private $subject;
	private $to;
	private $name;
	private $cc;
	private $bcc;

	public function __construct()
	{
		$this->swiftMessage = new Swift_Message();
	}

	public function to( $to, $name = null )
	{
		$this->to = $to;
		$this->name = $$name;

		return $this;
	}

	public function cc( $cc )
	{
		$this->cc = $cc;

		return $this;
	}

	public function bcc( $bcc )
	{
		$this->bcc = $bcc;

		return $this;
	}

	public function subject( $subject )
	{
		$this->subject = $subject;

		return $this;
	}

	public function message( $message )
	{
		$this->message = $message;

		return $this;
	}

	public function optionalParameters( $parameters )
	{
		$this->parameters = $parameters;

		return $this;
	}

	/**
	 * @return Mail
	 */
	public function reply()
	{
		$this->setHeader( 'In-Reply-To', $this->getThreatId() );
		$body = $this->getBody();

		return new Mail( $this->service->users_messages->send( 'me', $body, $this->parameters ) );
	}

	//TODO: How to send email
	public function send()
	{
		return new Mail( $this->service->users_messages->send( 'me', $this->body, $this->parameters ) );
	}

	/**
	 * @return Google_Service_Gmail_Message
	 */
	private function getBody()
	{
		$body = new Google_Service_Gmail_Message();

		$this->swiftMessage
			->setSubject( $this->subject )
			->setTo( $this->to, $this->name )
			->setCc( $this->cc )
			->setBcc( $this->bcc )
			->setBody( $this->message, 'text/html' );

		//TODO add attachments

		$body->setRaw( $this->base64_encode( $this->swiftMessage->toString() ) );

		return $body;
	}

	public function setHeader( $header, $value )
	{
		$headers = $this->swiftMessage->getHeaders();

		$headers->addTextHeader( $header, $value );

	}

	private function base64_encode( $data )
	{
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}
}