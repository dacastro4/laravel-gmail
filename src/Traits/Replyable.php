<?php

declare(strict_types=1);

namespace Dacastro4\LaravelGmail\Traits;

use Dacastro4\LaravelGmail\Services\Message\Mail;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;
use Illuminate\Container\Container;
use Illuminate\Mail\Markdown;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * @property Google_Service_Gmail $service
 */
trait Replyable
{
    use HasHeaders;

    private Email $symfonyEmail;

    private array $parameters = [];

    private ?string $message = null;

    private ?string $subject = null;

    private ?string $from = null;

    private ?string $nameFrom = null;

    private string|array|null $to = null;

    private ?string $nameTo = null;

    private string|array|null $cc = null;

    private ?string $nameCc = null;

    private string|array|null $bcc = null;

    private ?string $nameBcc = null;

    private array $attachments = [];

    private int $priority = 2;

    public function __construct()
    {
        $this->symfonyEmail = new Email;
    }

    /**
     * Receives the recipient's
     * If multiple recipients will receive the message an array should be used.
     * Example: array('receiver@domain.org', 'other@domain.org' => 'A name')
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * @return Replyable
     */
    public function to(string|array $to, ?string $name = null): self
    {
        $this->to = $this->emailList($to, $name);
        $this->nameTo = $name;

        return $this;
    }

    public function from(string $from, ?string $name = null): self
    {
        $this->from = $from;
        $this->nameFrom = $name;

        return $this;
    }

    public function cc(string|array $cc, ?string $name = null): self
    {
        $this->cc = $this->emailList($cc, $name);
        $this->nameCc = $name;

        return $this;
    }

    private function emailList(string|array $list, string|array|null $name = null): array|string
    {
        if (is_array($list)) {
            return $this->convertEmailList($list, is_array($name) ? $name : (isset($name) ? [$name] : null));
        }

        return $list;
    }

    private function convertEmailList(array $emails, ?array $name = null): array
    {
        $newList = [];
        $count = 0;
        foreach ($emails as $key => $email) {
            $emailName = $name[$count] ?? explode('@', $email)[0];
            $newList[$email] = $emailName;
            $count = $count + 1;
        }

        return $newList;
    }

    public function bcc(string|array $bcc, ?string $name = null): self
    {
        $this->bcc = $this->emailList($bcc, $name);
        $this->nameBcc = $name;

        return $this;
    }

    public function subject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return Replyable
     *
     * @throws \Throwable
     */
    public function view(string $view, array $data = [], array $mergeData = []): self
    {
        $this->message = view($view, $data, $mergeData)->render();

        return $this;
    }

    /**
     * loads markdown file for message body
     *
     * @return Replyable
     *
     * @throws \Throwable
     */
    public function markdown(string $markdown_view, array $data = []): self
    {
        $markdown = Container::getInstance()->make(Markdown::class);

        if (config('mail.markdown.theme')) {
            $markdown->theme(config('mail.markdown.theme'));
        }

        $this->message = $markdown->render($markdown_view, $data);

        return $this;
    }

    /**
     * @return Replyable
     */
    public function message(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Attaches new file to the email from the Storage folder
     *
     * @param  array  $files  comma separated of files
     * @return Replyable
     *
     * @throws \Exception
     */
    public function attach(string ...$files): self
    {
        foreach ($files as $file) {
            if (! file_exists($file)) {
                throw new FileNotFoundException($file);
            }

            $this->attachments[] = $file;
        }

        return $this;
    }

    /**
     * The value is an integer where 1 is the highest priority and 5 is the lowest.
     *
     * @return Replyable
     */
    public function priority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return Replyable
     */
    public function optionalParameters(array $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function reply(): Mail
    {
        if (! $this->getId()) {
            throw new \Exception('This is a new email. Use send().');
        }

        $this->setReplyThread();
        $this->setReplySubject();
        $this->setReplyTo();
        $this->setReplyFrom();
        $body = $this->getMessageBody();
        $body->setThreadId($this->getThreadId());

        return new Mail($this->getTokenRepository(), $this->service->users_messages->send('me', $body, $this->parameters));
    }

    abstract public function getId(): string;

    private function setReplyThread(): void
    {
        $threadId = $this->getThreadId();
        if ($threadId) {
            $this->setHeader('In-Reply-To', $this->getMessageIdHeader());
            $this->setHeader('References', $this->getHeader('References'));
            $this->setHeader('Message-ID', $this->getMessageIdHeader());
        }
    }

    private function getMessageIdHeader(): ?string
    {
        if ($messageId = $this->getHeader('Message-ID')) {
            return $messageId;
        }

        if ($messageId = $this->getHeader('Message-Id')) {
            return $messageId;
        }

        return null;
    }

    abstract public function getThreadId(): ?string;

    public function setHeader(string $header, string $value): void
    {
        $headers = $this->symfonyEmail->getHeaders();

        $headers->addTextHeader($header, $value);
    }

    private function setReplySubject(): void
    {
        if (! $this->subject) {
            $this->subject = $this->getSubject();
        }
    }

    private function setReplyTo(): void
    {
        if (! $this->to) {
            $replyTo = $this->getReplyTo();

            $this->to = $replyTo['email'];
            $this->nameTo = $replyTo['name'];
        }
    }

    private function setReplyFrom(): void
    {
        if (! $this->from) {
            $this->from = $this->getUser();
            if (! $this->from) {
                throw new \Exception('Reply from is not defined');
            }
        }
    }

    abstract public function getSubject(): ?string;

    abstract public function getReplyTo(): array;

    abstract public function getUser(): ?string;

    private function getMessageBody(): Google_Service_Gmail_Message
    {
        $body = new Google_Service_Gmail_Message;

        $this->symfonyEmail
            ->from($this->fromAddress())
            ->to($this->toAddress())
            ->subject($this->subject)
            ->html($this->message)
            ->priority($this->priority);

        // Fixes the issue: "An address can be an instance of Address or a string ("null" given)."
        if (isset($this->cc)) {
            $this->symfonyEmail->cc($this->returnCopies($this->cc));
        }
        if (isset($this->bcc)) {
            $this->symfonyEmail->bcc($this->returnCopies($this->bcc));
        }

        foreach ($this->attachments as $file) {
            $this->symfonyEmail->attachFromPath($file);
        }

        $body->setRaw($this->base64_encode($this->symfonyEmail->toString()));

        return $body;
    }

    public function returnCopies(array|string $cc): array|string
    {
        if ($cc) {
            $final = $this->cc;

            if (is_array($this->cc)) {
                foreach ($this->cc as $emailCc => $nameCc) {
                    $final[] = new Address($emailCc, $nameCc);
                }
            }

            return $final;
        }

        return [];
    }

    public function toAddress(): Address|array
    {
        if ($this->to) {
            return new Address($this->to, $this->nameTo ?: '');
        }

        return [];
    }

    public function fromAddress(): Address|array
    {
        if ($this->from) {
            return new Address($this->from, $this->nameFrom ?: '');
        }

        return [];
    }

    private function base64_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), ['+' => '-', '/' => '_']), '=');
    }

    public function send(): self
    {
        $body = $this->getMessageBody();

        $this->setMessage($this->service->users_messages->send('me', $body, $this->parameters));

        return $this;
    }

    abstract protected function setMessage(\Google_Service_Gmail_Message $message): void;
}
