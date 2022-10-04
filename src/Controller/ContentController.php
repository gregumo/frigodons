<?php

namespace App\Controller;

use App\Repository\CleaningDateRepository;
use App\Repository\SupervisingDateRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class ContentController extends AbstractController
{
    #[Route('/', name: 'app_content_home')]
    public function home(): Response
    {
        return $this->render('content/home.html.twig');
    }

    #[Route('/guide-du-benevole', name: 'app_content_volunteer')]
    public function index(): Response
    {
        return $this->render('content/volunteer_guide.html.twig');
    }
    #[Route('/email', name: 'email')]
    public function email(CleaningDateRepository $cleaningDateRepo, SupervisingDateRepository $supervisingDateRepo, Environment $twig, MailerInterface $mailer): Response
    {
        $user = $this->getUser();

        $cleaningDates = $cleaningDateRepo->getNextWeekDatesForUser($user);
        $supervisingDates = $user->isManager() ? $supervisingDateRepo->getNextWeekDatesForUser($user) : [];
        if(!empty($cleaningDates) || !empty($supervisingDates)) {
            $userAndDates = compact('user', 'cleaningDates', 'supervisingDates');
        }
        $from = new Address($this->getParameter('app.mail_from'), $this->getParameter('app.mail_from_name'));
        $replyTo = new Address($this->getParameter('app.mail_reply_to'), $this->getParameter('app.mail_reply_to_name'));
        $email = (new Email())
            ->from($from)
            ->replyTo($replyTo)
            ->to('gregoire.humeau@gmail.com')
            ->subject('Test de sujet')
            ->html($twig->render('mail/callback.html.twig', $userAndDates));

        $mailer->send($email);

        return $this->render('mail/callback.html.twig', $userAndDates);
    }
}
