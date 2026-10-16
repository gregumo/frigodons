<?php

declare(strict_types=1);

namespace App\Mail;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

/**
 * Creates the emails sent by La Cordée, with the right sender.
 */
final readonly class EmailFactory
{
    public function __construct(
        private string $from,
        private string $fromName,
        private string $replyTo,
        private string $replyToName,
    ) {}

    /**
     * Answers go to the mailbox of La Cordée.
     */
    public function create(): TemplatedEmail
    {
        return new TemplatedEmail()
            ->from(new Address($this->from, $this->fromName))
            ->replyTo($this->teamAddress());
    }

    /**
     * The mailbox of La Cordée, which receives the messages of the contact form.
     */
    public function teamAddress(): Address
    {
        return new Address($this->replyTo, $this->replyToName);
    }
}
