<?php

namespace App\Controller;

use App\Repository\CleaningDateRepository;
use App\Repository\SupervisingDateRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
    public function email(UserRepository $userRepo, CleaningDateRepository $cleaningDateRepo, SupervisingDateRepository $supervisingDateRepo): Response
    {
        $users = $userRepo->findByCallbackMailOptIn(true);

        $usersAndDates = [];
        foreach ($users as $user) {
            $cleaningDates = $cleaningDateRepo->getNextWeekDatesForUser($user);
            $supervisingDates = $user->isManager() ? $supervisingDateRepo->getNextWeekDatesForUser($user) : [];
            if(!empty($cleaningDates) || !empty($supervisingDates)) {
                $usersAndDates[] = compact('user', 'cleaningDates', 'supervisingDates');
            }
        }

        return $this->render('mail/callback.html.twig', $usersAndDates[2]);
    }
}
