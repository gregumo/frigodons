<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\MailBatch;
use App\Mail\EmailFactory;
use App\Repository\CleaningDateRepository;
use App\Repository\MailBatchRepository;
use App\Repository\SupervisingDateRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;

#[AsCommand(
    name: 'app:callback-mail',
    description: 'Envoie aux bénévoles le rappel de leurs dates de la semaine prochaine',
)]
final readonly class CallbackMailCommand
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailBatchRepository $mailBatchRepository,
        private UserRepository $userRepository,
        private CleaningDateRepository $cleaningDateRepository,
        private SupervisingDateRepository $supervisingDateRepository,
        private MailerInterface $mailer,
        private EmailFactory $emailFactory,
    ) {}

    public function __invoke(SymfonyStyle $io): int
    {
        if ($this->mailBatchRepository->existsSince(MailBatch::TYPE_CALLBACK, new \DateTimeImmutable('today'))) {
            $io->warning("Les mails de rappel ont déjà été envoyés aujourd'hui.");

            return Command::SUCCESS;
        }

        $users = $this->userRepository->findBy(['callbackMailOptIn' => true]);
        $io->note(sprintf('%d utilisateur(s) acceptent les mails de rappel de leurs dates.', \count($users)));

        $start = new \DateTimeImmutable('next monday');
        $end = $start->modify('+6 days');

        $recipients = [];
        foreach ($users as $user) {
            $cleaningDates = $this->cleaningDateRepository->findForUserBetween($user, $start, $end);
            $supervisingDates = $user->isManager()
                ? $this->supervisingDateRepository->findForUserBetween($user, $start, $end)
                : [];
            if ([] === $cleaningDates && [] === $supervisingDates) {
                continue;
            }

            $email = $this->emailFactory
                ->create()
                ->to((string) $user->getEmail())
                ->subject(self::subject(\count($cleaningDates), \count($supervisingDates)))
                ->htmlTemplate('email/callback.html.twig')
                ->context([
                    'user' => $user,
                    'cleaningDates' => $cleaningDates,
                    'supervisingDates' => $supervisingDates,
                ]);
            $this->mailer->send($email);

            $recipients[] = (string) $user->getEmail();
        }

        $this->entityManager->persist(new MailBatch(MailBatch::TYPE_CALLBACK, $recipients));
        $this->entityManager->flush();

        $io->success(sprintf('%d mail(s) de rappel envoyé(s).', \count($recipients)));

        return Command::SUCCESS;
    }

    private static function subject(int $cleaningCount, int $supervisingCount): string
    {
        $days = [];
        if ($cleaningCount > 0) {
            $days[] = sprintf('%d %s de bénévolat', $cleaningCount, $cleaningCount > 1 ? 'jours' : 'jour');
        }
        if ($supervisingCount > 0) {
            $days[] = sprintf('%d %s de responsable', $supervisingCount, $supervisingCount > 1 ? 'jours' : 'jour');
        }

        return sprintf("Frig'o dons | Vos dates - Vous avez %s la semaine prochaine", implode(' et ', $days));
    }
}
