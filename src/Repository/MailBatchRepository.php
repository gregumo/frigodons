<?php

namespace App\Repository;

use App\Entity\MailBatch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Constraints\Date;

/**
 * @extends ServiceEntityRepository<MailBatch>
 *
 * @method MailBatch|null find($id, $lockMode = null, $lockVersion = null)
 * @method MailBatch|null findOneBy(array $criteria, array $orderBy = null)
 * @method MailBatch[]    findAll()
 * @method MailBatch[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MailBatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailBatch::class);
    }

    public function todayBatchExists(string $type): bool
    {
        $result = $this->createQueryBuilder('m')
            ->select('count(m.id)')
            ->where('m.type = :type')
            ->andWhere('m.send_at >= :startDateTime')
            ->andWhere('m.send_at <= :endDateTime')
            ->setParameters([
                'type' => $type,
                'startDateTime' => (new \DateTime())->format('Y-m-d') . ' 00:00:00',
                'endDateTime' => (new \DateTime())->format('Y-m-d') . ' 23:59:59',
            ])
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }

    public function previousSundayBatchExists(string $type): bool
    {
        $result = $this->createQueryBuilder('m')
            ->select('count(m.id)')
            ->where('m.type = :type')
            ->andWhere('m.send_at >= :startDateTime')
            ->andWhere('m.send_at <= :endDateTime')
            ->setParameters([
                'type' => $type,
                'startDateTime' => (new \DateTime())->modify('previous sunday')->format('Y-m-d') . ' 00:00:00',
                'endDateTime' => (new \DateTime())->modify('previous sunday')->format('Y-m-d') . ' 23:59:59',
            ])
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }
}
