<?php

namespace App\Command;

use App\Entity\MailBatch;
use App\Repository\CleaningDateRepository;
use App\Repository\MailBatchRepository;
use App\Repository\SupervisingDateRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

#[AsCommand(
    name: 'app:missing-volunteer-mail',
    description: 'Envoi les mails de rappel lorsqu\'il manque des bénévoles pour la semaine à venir.',
)]
class MissingVolunteerMailCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailBatchRepository $mailBatchRepo,
        private UserRepository $userRepo,
        private CleaningDateRepository $cleaningDateRepo,
        private SupervisingDateRepository $supervisingDateRepo,
        private MailerInterface $mailer,
        private ContainerBagInterface $params,
        private TranslatorInterface $translator,
        private Environment $twig

    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);


        if($this->mailBatchRepo->todayBatchExists(MailBatch::TYPE_MISSING_VOLUNTEER)){
            $io->error('La commande a déjà été lancée aujourd\'hui');

            return Command::FAILURE;
        }

        if($this->mailBatchRepo->previousSundayBatchExists(MailBatch::TYPE_MISSING_VOLUNTEER)){
            $io->warning('La commande a été lancée dimanche dernier, on attend une semaine de plus');

            return Command::INVALID;
        }

        die();

        $users = $this->userRepo->findByMissingVolunteerMailOptIn(true);

        $io->note(sprintf('%s utilisateur(s) acceptent les mails d\'appel aux bénévoles', count($users)));

        $next2WeeksDays = '';

        $cleaningBookedDays = $this->cleaningDateRepo->getNext2WeeksBookedDays();
        $supervisingBookedDays = $this->supervisingDateRepo->getNext2WeeksBookedDays();

        $usersAndDates = [];
        foreach ($users as $user) {
            if(!empty($cleaningDates) || !empty($supervisingDates)) {
                $usersAndDates[] = compact('user', 'cleaningDates', 'supervisingDates');
            }
        }

        $io->note(sprintf('Parmi eux, %s ont au moins une date la semaine prochaine', count($usersAndDates)));

        $recipients = [];
        foreach ($usersAndDates as $mailData) {
            $recipients[] = $recipient = $mailData['user']->getEmail();
            $email = (new Email())
                ->from($this->params->get('app.mail_from'))
                ->to($recipient)
                ->subject($this->translator->trans('callback.title', [
                    'cleaningCount' => count($mailData['cleaningDates']),
                    'supervisingCount' => count($mailData['supervisingDates']),
                ], 'mail'))
                ->html($this->twig->render('mail/callback.html.twig', $mailData));

            $this->mailer->send($email);
        }

        $mailBatch = (new MailBatch())
            ->setType(MailBatch::TYPE_CALLBACK)
            ->setSendAt(new \DateTime())
            ->setRecipients($recipients);

        $this->entityManager->persist($mailBatch);
        $this->entityManager->flush();

        $io->success(sprintf('Les %s mail(s) ont été envoyé(s)', count($recipients)));

        return Command::SUCCESS;
    }
}
