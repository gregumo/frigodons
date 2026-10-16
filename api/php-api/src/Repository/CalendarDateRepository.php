<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CalendarDate;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @template T of CalendarDate
 *
 * @extends ServiceEntityRepository<T>
 */
abstract class CalendarDateRepository extends ServiceEntityRepository
{
    /**
     * The name of the association to the user in the entity (eg. `cleaner`).
     */
    abstract protected function userField(): string;

    /**
     * Counts the dates of the user between the 1st of January and yesterday.
     */
    public function countYearDoneDatesForUser(User $user): int
    {
        return $this->countForUserBetween(
            $user,
            new \DateTimeImmutable('first day of january this year'),
            new \DateTimeImmutable('yesterday'),
        );
    }

    /**
     * Counts the dates of the user between today and the 31st of December.
     */
    public function countYearScheduledDatesForUser(User $user): int
    {
        return $this->countForUserBetween(
            $user,
            new \DateTimeImmutable('today'),
            new \DateTimeImmutable('last day of december this year'),
        );
    }

    /**
     * @return list<T>
     */
    public function findForUserBetween(User $user, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        /** @var list<T> */
        return $this
            ->createQueryBuilder('d')
            ->where(sprintf('d.%s = :user', $this->userField()))
            ->andWhere('d.day BETWEEN :start AND :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start->format('Y-m-d'))
            ->setParameter('end', $end->format('Y-m-d'))
            ->orderBy('d.day', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns the days between the two dates (included) that nobody has booked yet.
     *
     * @return list<\DateTimeImmutable>
     */
    public function findAvailableDaysBetween(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        /** @var list<string> $booked Scalar results are not converted, days are `Y-m-d` strings */
        $booked = $this
            ->createQueryBuilder('d')
            ->select('d.day')
            ->where('d.day BETWEEN :start AND :end')
            ->setParameter('start', $start->format('Y-m-d'))
            ->setParameter('end', $end->format('Y-m-d'))
            ->getQuery()
            ->getSingleColumnResult();

        $availableDays = [];
        for ($day = $start->setTime(0, 0); $day <= $end; $day = $day->modify('+1 day')) {
            if (!\in_array($day->format('Y-m-d'), $booked, true)) {
                $availableDays[] = $day;
            }
        }

        return $availableDays;
    }

    private function countForUserBetween(User $user, \DateTimeImmutable $start, \DateTimeImmutable $end): int
    {
        return (int) $this
            ->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where(sprintf('d.%s = :user', $this->userField()))
            ->andWhere('d.day BETWEEN :start AND :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start->format('Y-m-d'))
            ->setParameter('end', $end->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();
    }
}
