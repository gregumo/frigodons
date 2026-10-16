<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Mail\EmailFactory;
use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

/**
 * Sends an email to `ALERT_EMAIL` when a scheduled task (eg. a mail command) fails.
 */
#[AsEventListener]
final readonly class ScheduledTaskFailureListener
{
    public function __construct(
        private MailerInterface $mailer,
        private EmailFactory $emailFactory,
        private string $alertEmail,
    ) {}

    public function __invoke(WorkerMessageFailedEvent $event): void
    {
        if ('' === $this->alertEmail || $event->willRetry()) {
            return;
        }

        $message = $event->getEnvelope()->getMessage();
        $task = $message instanceof RunCommandMessage ? $message->input : $message::class;

        $email = $this->emailFactory
            ->create()
            ->to($this->alertEmail)
            ->subject(sprintf("[Frig'o dons] Échec de la tâche %s", $task))
            ->text((string) $event->getThrowable());

        $this->mailer->send($email);
    }
}
