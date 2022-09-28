<?php

namespace App\Repository;

use App\Entity\CleaningDate;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CleaningDate|null find($id, $lockMode = null, $lockVersion = null)
 * @method CleaningDate|null findOneBy(array $criteria, array $orderBy = null)
 * @method CleaningDate[]    findAll()
 * @method CleaningDate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CleaningDateRepository extends DateRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CleaningDate::class);
    }

    public function findScheduledDatesForUser(User $user, $userFieldName = null): array
    {
        return parent::findScheduledDatesForUser($user, 'cleaner');
    }

    public function countYearDoneDatesForUser(User $user, $userFieldName = null): int
    {
        return parent::countYearDoneDatesForUser($user, 'cleaner');
    }

    public function countYearScheduledDatesForUser(User $user, $userFieldName = null): int
    {
        return parent::countYearScheduledDatesForUser($user, 'cleaner');
    }

    public function getNextWeekDatesForUser(User $user, $userFieldName = null): array
    {
        return parent::getNextWeekDatesForUser($user, 'cleaner');
    }

    public function getNext2WeeksAvailableDays($userFieldName = null): array
    {
        return parent::getNext2WeeksAvailableDays('cleaner');
    }
}
