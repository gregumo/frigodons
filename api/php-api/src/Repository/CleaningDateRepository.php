<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CleaningDate;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CalendarDateRepository<CleaningDate>
 */
class CleaningDateRepository extends CalendarDateRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CleaningDate::class);
    }

    protected function userField(): string
    {
        return 'cleaner';
    }
}
