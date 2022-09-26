<?php

namespace App\Controller;

use App\Entity\CleaningDate;
use App\Entity\SupervisingDate;
use App\Entity\User;
use App\Form\UserType;
use App\Utils\ParticipationHelper;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/mes-informations', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request,  ManagerRegistry $doctrine, ParticipationHelper $participationHelper): Response
    {
        $em = $doctrine->getManager();
        $user = $this->getUser();

        $cleaningDates = $doctrine->getRepository(CleaningDate::class)->findScheduledDatesForUser($user);
        $supervisingDates = $user->isManager() ? $doctrine->getRepository(SupervisingDate::class)->findScheduledDatesForUser($user) : [];

        $participationContent = $participationHelper->render();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if($form->isValid()) {
                $em->flush();
                $this->addFlash('success', 'Vos informations ont bien été mises à jour.');

                return $this->redirectToRoute('app_user_edit');
            }
            $em->refresh($user);
        }

        return $this->renderForm('user/edit.html.twig', compact(
            'user', 'form', 'cleaningDates', 'supervisingDates', 'participationContent'
        ));
    }

    #[Route('/annuaire', name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request,  ManagerRegistry $doctrine): Response
    {
        $volunteers = $doctrine->getRepository(User::class)->getVolunteers();
        $managers = $doctrine->getRepository(User::class)->getManagers();

        return $this->renderForm('user/index.html.twig', compact( 'volunteers', 'managers'));
    }
}
