<?php

namespace App\Utils;

use App\Entity\CleaningDate;
use App\Entity\SupervisingDate;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

class ParticipationHelper
{
    private Environment $twig;
    private ManagerRegistry $doctrine;
    private TokenStorageInterface $tokenStorage;

    public function __construct(
        Environment     $twig,
        ManagerRegistry $doctrine,
        TokenStorageInterface $tokenStorage
    )
    {
        $this->twig = $twig;
        $this->doctrine = $doctrine;
        $this->tokenStorage = $tokenStorage;
    }

    public function render(): string
    {
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        $yearDoneCleaningDatesCount = $this->doctrine->getRepository(CleaningDate::class)->countYearDoneDatesForUser($user);
        $yearScheduledCleaningDatesCount = $this->doctrine->getRepository(CleaningDate::class)->countYearScheduledDatesForUser($user);

        $yearDoneSupervisingDatesCount = $user->isManager() ? $this->doctrine->getRepository(SupervisingDate::class)->countYearDoneDatesForUser($user) : 0;
        $yearScheduledSupervisingDatesCount = $user->isManager() ? $this->doctrine->getRepository(SupervisingDate::class)->countYearScheduledDatesForUser($user) : [];

        $volunteersCount = $this->doctrine->getRepository(User::class)->countVolunteers();
        $managersCount = $this->doctrine->getRepository(User::class)->countManagers();

        return $this->twig->render('part/participation.html.twig', compact(
            'yearDoneCleaningDatesCount', 'yearScheduledCleaningDatesCount',
            'yearDoneSupervisingDatesCount', 'yearScheduledSupervisingDatesCount',
            'volunteersCount', 'managersCount'
        ));
    }

}
