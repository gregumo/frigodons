<?php

namespace App\Repository;

use App\Entity\CleaningDate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CleaningDate>
 *
 * @method CleaningDate|null find($id, $lockMode = null, $lockVersion = null)
 * @method CleaningDate|null findOneBy(array $criteria, array $orderBy = null)
 * @method CleaningDate[]    findAll()
 * @method CleaningDate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CleaningDateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CleaningDate::class);
    }

    public function add(CleaningDate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CleaningDate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findBetweenDates(string $startDate, string $endDate): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.day BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('c.day', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
