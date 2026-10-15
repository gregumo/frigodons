<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CleaningDate;
use App\Entity\SupervisingDate;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Emails are compared case-insensitively, as they used to be typed by hand by volunteers.
     */
    public function findOneByEmail(string $email): ?User
    {
        return $this
            ->createQueryBuilder('u')
            ->where('LOWER(u.email) = LOWER(:email)')
            ->setParameter('email', trim($email))
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countAll(): int
    {
        return $this->count();
    }

    public function countManagers(): int
    {
        return (int) $this
            ->getEntityManager()
            ->getConnection()
            ->fetchOne('SELECT COUNT(*) FROM "user" WHERE deleted_at IS NULL AND roles->>0 = :role', [
                'role' => User::ROLE_MANAGER,
            ]);
    }

    /**
     * Returns all users with their number of cleaning and supervising dates of the current year.
     *
     * @return list<User>
     */
    public function findWithYearDateCounts(): array
    {
        $year = (int) date('Y');

        /** @var list<array{0: User, cleaningCount: int, supervisingCount: int}> $rows */
        $rows = $this
            ->createQueryBuilder('u')
            ->addSelect(sprintf(
                '(SELECT COUNT(cd.id) FROM %s cd WHERE cd.cleaner = u AND cd.day BETWEEN :start AND :end) AS cleaningCount',
                CleaningDate::class,
            ))
            ->addSelect(sprintf(
                '(SELECT COUNT(sd.id) FROM %s sd WHERE sd.supervisor = u AND sd.day BETWEEN :start AND :end) AS supervisingCount',
                SupervisingDate::class,
            ))
            ->setParameter('start', sprintf('%d-01-01', $year))
            ->setParameter('end', sprintf('%d-12-31', $year))
            ->getQuery()
            ->getResult();

        return array_map(static function (array $row): User {
            $user = $row[0];
            $user->yearCleaningCount = (int) $row['cleaningCount'];
            $user->yearSupervisingCount = (int) $row['supervisingCount'];

            return $user;
        }, $rows);
    }
}
