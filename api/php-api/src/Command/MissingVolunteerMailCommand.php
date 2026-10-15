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
    name: 'app:missing-volunteer-mail',
    description: 'Envoie, toutes les deux semaines, les dates encore libres dans les deux semaines à venir',
)]
final readonly class MissingVolunteerMailCommand
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
        if ($this->mailBatchRepository->existsSince(
            MailBatch::TYPE_MISSING_VOLUNTEER,
            new \DateTimeImmutable('today -7 days'),
        )) {
            $io->warning('Les mails ont été envoyés il y a moins de deux semaines.');

            return Command::SUCCESS;
        }

        $start = new \DateTimeImmutable('next monday');
        $end = $start->modify('+13 days');
        $cleaningAvailableDays = $this->cleaningDateRepository->findAvailableDaysBetween($start, $end);
        $supervisingAvailableDays = $this->supervisingDateRepository->findAvailableDaysBetween($start, $end);

        if ([] === $cleaningAvailableDays && [] === $supervisingAvailableDays) {
            $io->warning("Toutes les dates sont réservées, aucun mail n'est envoyé.");

            return Command::SUCCESS;
        }

        $users = $this->userRepository->findBy(['missingVolunteerMailOptIn' => true]);
        $io->note(sprintf("%d utilisateur(s) acceptent les mails d'appel aux bénévoles.", \count($users)));

        $recipients = [];
        foreach ($users as $user) {
            // Volunteers can only book cleaning dates.
            if (!$user->isManager() && [] === $cleaningAvailableDays) {
                continue;
            }

            $email = $this->emailFactory
                ->create()
                ->to((string) $user->getEmail())
                ->subject(self::subject(
                    \count($cleaningAvailableDays),
                    $user->isManager() ? \count($supervisingAvailableDays) : 0,
                ))
                ->htmlTemplate('email/missing_volunteer.html.twig')
                ->context([
                    'user' => $user,
                    'cleaningAvailableDays' => $cleaningAvailableDays,
                    'supervisingAvailableDays' => $supervisingAvailableDays,
                ]);
            $this->mailer->send($email);

            $recipients[] = (string) $user->getEmail();
        }

        $this->entityManager->persist(new MailBatch(MailBatch::TYPE_MISSING_VOLUNTEER, $recipients));
        $this->entityManager->flush();

        $io->success(sprintf("%d mail(s) d'appel aux bénévoles envoyé(s).", \count($recipients)));

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
        $plural = \count($days) > 1 || $cleaningCount > 1 || $supervisingCount > 1;

        return sprintf(
            "Frig'o dons | Appel aux bénévoles - %s encore %s",
            implode(' et ', $days),
            $plural ? 'libres' : 'libre',
        );
    }
}
