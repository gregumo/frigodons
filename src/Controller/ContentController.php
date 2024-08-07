<?php

namespace App\Controller;

use App\Entity\KeyValueInfo;
use App\Repository\CleaningDateRepository;
use App\Repository\KeyValueInfoRepository;
use App\Repository\SupervisingDateRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ContentController extends AbstractController
{
    #[Route('/', name: 'app_content_home')]
    public function home(UserRepository $userRepo): Response
    {
        return $this->render('content/home.html.twig', );
    }

    #[Route('/devenir-benevole', name: 'app_content_volunteer')]
    public function index(UserRepository $userRepo): Response
    {
        $volunteerCount = count($userRepo->findAll());

        return $this->render('content/became_volunteer.html.twig', compact('volunteerCount'));
    }

    #[Route('/documents', name: 'app_content_documents')]
    public function documents(KeyValueInfoRepository $keyValueInfoRepo): Response
    {
        $closetPadlockCode =  $keyValueInfoRepo->findOneBy(['key' => 'closetPadlockCode']);
        $composterPadlockCode =  $keyValueInfoRepo->findOneBy(['key' => 'composterPadlockCode']);

        return $this->render('content/documents.html.twig', [
            'closetPadlockCode' => $closetPadlockCode?->getValue(),
            'composterPadlockCode' => $composterPadlockCode?->getValue(),
        ]);
    }

    #[Route('/email1/{sendMail}', name: 'email1')]
    public function email1(
        CleaningDateRepository    $cleaningDateRepo,
        SupervisingDateRepository $supervisingDateRepo,
        Environment               $twig,
        MailerInterface           $mailer,
        TranslatorInterface       $translator,
        bool                      $sendMail = false
    ): Response
    {
        $user = $this->getUser();

        $cleaningDates = $cleaningDateRepo->getNextWeekDatesForUser($user);
        $supervisingDates = $user->isManager() ? $supervisingDateRepo->getNextWeekDatesForUser($user) : [];
        $userAndDates = compact('user', 'cleaningDates', 'supervisingDates');
        $from = new Address($this->getParameter('app.mail_from'), $this->getParameter('app.mail_from_name'));
        $replyTo = new Address($this->getParameter('app.mail_reply_to'), $this->getParameter('app.mail_reply_to_name'));
        $email = (new Email())
            ->from($from)
            ->replyTo($replyTo)
            ->to('gregoire.humeau@gmail.com')
            ->subject($translator->trans('callback.title', [
                'cleaningCount' => count($userAndDates['cleaningDates']),
                'supervisingCount' => count($userAndDates['supervisingDates']),
            ], 'mail'))
            ->html($twig->render('mail/callback.html.twig', $userAndDates));

        if ($sendMail) {
            $mailer->send($email);
        }

        return $this->render('mail/callback.html.twig', $userAndDates);
    }

    #[Route('/email2/{sendMail}', name: 'email2')]
    public function email2(
        CleaningDateRepository    $cleaningDateRepo,
        SupervisingDateRepository $supervisingDateRepo,
        Environment               $twig,
        MailerInterface           $mailer,
        TranslatorInterface       $translator,
        bool                      $sendMail = false
    ): Response
    {
        $user = $this->getUser();

        $mailData = [
            'cleaningAvailableDays' => $cleaningDateRepo->getNext2WeeksAvailableDays(),
            'supervisingAvailableDays' => $supervisingDateRepo->getNext2WeeksAvailableDays(),
            'user' => $user
        ];

        $from = new Address($this->getParameter('app.mail_from'), $this->getParameter('app.mail_from_name'));
        $replyTo = new Address($this->getParameter('app.mail_reply_to'), $this->getParameter('app.mail_reply_to_name'));
        $email = (new Email())
            ->from($from)
            ->replyTo($replyTo)
            ->to('gregoire.humeau@gmail.com')
            ->subject($translator->trans('missing_volunteer.title', [
                'userType' => $user->isManager() ? 'manager' : 'volunteer',
                'cleaningCount' => count($mailData['cleaningAvailableDays']),
                'supervisingCount' => count($mailData['supervisingAvailableDays']),
            ], 'mail'))
            ->html($twig->render('mail/missing_volunteer.html.twig', $mailData));

        if ($sendMail) {
            $mailer->send($email);
        }

        return $this->render('mail/missing_volunteer.html.twig', $mailData);
    }
}
