<?php
/**
 * Created by Gregoire Humeau
 * gregoire.humeau@gmail.com
 * 24/09/2022
 */

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

abstract class DateRepository extends ServiceEntityRepository
{
    public function findBetweenDates(string $startDate, string $endDate): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.day BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('d.day', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function findScheduledDatesForUser(User $user, $userFieldName): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.day >= :startDate')
            ->andWhere("d.$userFieldName = :user")
            ->setParameters([
                'startDate' => (new \DateTime())->format('Y-m-d'),
                'user' => $user
            ])
            ->orderBy('d.day', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countYearDoneDatesForUser(User $user, $userFieldName): int
    {
        return $this->createQueryBuilder('d')
            ->select('count(d.id)')
            ->where('d.day < :date')
            ->andWhere("d.$userFieldName = :user")
            ->setParameters([
                'date' => (new \DateTime())->format('Y-m-d'),
                'user' => $user
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countYearScheduledDatesForUser(User $user, $userFieldName): int
    {
        return $this->createQueryBuilder('d')
            ->select('count(d.id)')
            ->where('d.day >= :startDate')
            ->andWhere('d.day <= :endDate')
            ->andWhere("d.$userFieldName = :user")
            ->setParameters([
                'startDate' => (new \DateTime())->format('Y-m-d'),
                'endDate' => (new \DateTime())->format('Y') . '-12-31',
                'user' => $user
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }
}
