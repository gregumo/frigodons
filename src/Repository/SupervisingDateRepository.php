<?php

namespace App\Repository;

use App\Entity\SupervisingDate;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SupervisingDate|null find($id, $lockMode = null, $lockVersion = null)
 * @method SupervisingDate|null findOneBy(array $criteria, array $orderBy = null)
 * @method SupervisingDate[]    findAll()
 * @method SupervisingDate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SupervisingDateRepository extends DateRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupervisingDate::class);
    }

    public function findScheduledDatesForUser(User $user, $userFieldName = null): array
    {
        return parent::findScheduledDatesForUser($user,'supervisor');
    }

    public function countYearDoneDatesForUser(User $user, $userFieldName = null): int
    {
        return parent::countYearDoneDatesForUser($user, 'supervisor');
    }

    public function countYearScheduledDatesForUser(User $user, $userFieldName = null): int
    {
        return parent::countYearScheduledDatesForUser($user, 'supervisor');
    }

    public function getNextWeekDatesForUser(User $user, $userFieldName = null): array
    {
        return parent::getNextWeekDatesForUser($user, 'supervisor');
    }

    public function getNext2WeeksAvailableDays($userFieldName = null): array
    {
        return parent::getNext2WeeksAvailableDays('supervisor');
    }
}
