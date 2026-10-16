<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SupervisingDate;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CalendarDateRepository<SupervisingDate>
 */
class SupervisingDateRepository extends CalendarDateRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupervisingDate::class);
    }

    protected function userField(): string
    {
        return 'supervisor';
    }
}
