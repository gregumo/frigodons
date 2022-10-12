<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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
        return $this->getUserQbForRole('ROLE_VOLUNTEER')
            ->getQuery()
            ->getResult();
    }

    public function getManagers(): array
    {
        return $this->getUserQbForRole('ROLE_MANAGER')
            ->addSelect('(SELECT COUNT(sd) FROM App\Entity\SupervisingDate sd WHERE sd.supervisor = u AND sd.day >= :startDate AND sd.day <= :endDate) AS supervisingCount')
            ->getQuery()
            ->getResult();
    }

    private function getUserQbForRole(string $role): QueryBuilder
    {
        return $this->createQueryBuilder('u')
            ->select('u.id, UNACCENT(u.firstname) as unaccent_firstname', 'UNACCENT(u.lastname) as unaccent_lastname', 'u.firstname', 'u.lastname', 'u.email', 'u.phone', 'u.callbackMailOptIn', 'u.missingVolunteerMailOptIn')
            ->addSelect('(SELECT COUNT(cd) FROM App\Entity\CleaningDate cd WHERE cd.cleaner = u AND cd.day >= :startDate AND cd.day <= :endDate) AS cleaningCount')
            ->where("JSON_GET_TEXT(u.roles,0) = :role")
            ->orderBy('unaccent_firstname')
            ->addOrderBy('unaccent_lastname')
            ->setParameters([
                'role' => $role,
                'startDate' => (new \DateTime())->format('Y') . '-01-01',
                'endDate' => (new \DateTime())->format('Y') . '-12-31',
            ]);
    }
}
