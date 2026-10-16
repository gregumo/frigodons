<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MailBatch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MailBatch>
 */
class MailBatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailBatch::class);
    }

    public function existsSince(string $type, \DateTimeImmutable $since): bool
    {
        return (
            (int) $this
                ->createQueryBuilder('m')
                ->select('COUNT(m.id)')
                ->where('m.type = :type')
                ->andWhere('m.sendAt >= :since')
                ->setParameter('type', $type)
                ->setParameter('since', $since)
                ->getQuery()
                ->getSingleScalarResult() > 0
        );
    }
}
