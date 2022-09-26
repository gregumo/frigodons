<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function countVolunteers(): int
    {
        return $this->createQueryBuilder('u')
            ->select('count(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countManagers(): int
    {
        return $this->createQueryBuilder('u')
            ->select('count(u.id)')
            ->where("JSON_GET_TEXT(u.roles,0) = 'ROLE_MANAGER' ")
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getVolunteers(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.firstname', 'u.lastname', 'u.email', 'u.phone')
            ->addSelect('(SELECT COUNT(cd) FROM App\Entity\CleaningDate cd WHERE cd.cleaner = u) AS cleaningCount')
            ->where("JSON_GET_TEXT(u.roles,0) = 'ROLE_VOLUNTEER'")
            ->orderBy('u.firstname')
            ->addOrderBy('u.lastname')
/*            ->setParameters([
                'startDate' => (new \DateTime())->format('Y') . '-01-01',
                'endDate' => (new \DateTime())->format('Y') . '-12-31',
            ])*/
            ->getQuery()->getResult();
    }

    public function getManagers(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.firstname', 'u.lastname', 'u.email', 'u.phone')
            ->addSelect('(SELECT COUNT(cd) FROM App\Entity\CleaningDate cd WHERE cd.cleaner = u) AS cleaningCount')
            ->addSelect('(SELECT COUNT(sd) FROM App\Entity\SupervisingDate sd WHERE sd.supervisor = u) AS supervisingCount')
            ->where("JSON_GET_TEXT(u.roles,0) = 'ROLE_MANAGER' ")
            ->orderBy('u.firstname')
            ->addOrderBy('u.lastname')
            ->getQuery()
            ->getResult();
    }
}
