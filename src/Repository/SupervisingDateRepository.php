<?php

namespace App\Repository;

use App\Entity\SupervisingDate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SupervisingDate>
 *
 * @method SupervisingDate|null find($id, $lockMode = null, $lockVersion = null)
 * @method SupervisingDate|null findOneBy(array $criteria, array $orderBy = null)
 * @method SupervisingDate[]    findAll()
 * @method SupervisingDate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SupervisingDateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupervisingDate::class);
    }

    public function add(SupervisingDate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SupervisingDate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findBetweenDates(string $startDate, string $endDate): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.day BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('s.day', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }
}
