<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Contact;
use App\Mail\EmailFactory;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * @implements ProcessorInterface<Contact, void>
 */
final readonly class ContactProcessor implements ProcessorInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private EmailFactory $emailFactory,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (null !== $data->website && '' !== $data->website) {
            return;
        }

        $email = $this->emailFactory
            ->create()
            ->to($this->emailFactory->teamAddress())
            ->replyTo(new Address(trim($data->email), sprintf('%s %s', trim($data->firstname), trim($data->lastname))))
            ->subject(sprintf('Message de %s %s sur frigodons.org', trim($data->firstname), trim($data->lastname)))
            ->htmlTemplate('email/contact.html.twig')
            ->context(['contact' => $data]);

        $this->mailer->send($email);
    }
}
