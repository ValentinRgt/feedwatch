<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SourceError;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SourceError>
 */
class SourceErrorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SourceError::class);
    }

    /**
     * @param SourceError $sourceError
     * @param bool $flush
     * @return void
     */
    public function save(SourceError $sourceError, bool $flush = false): void
    {
        $this->getEntityManager()->persist($sourceError);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param SourceError $sourceError
     * @param bool $flush
     * @return void
     */
    public function remove(SourceError $sourceError, bool $flush = false): void
    {
        $this->getEntityManager()->remove($sourceError);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param int $limit
     * @return SourceError[]
     */
    public function findRecent(int $limit): array
    {
        return $this->createQueryBuilder('e')
            ->innerJoin('e.source', 's')
            ->addSelect('s')
            ->orderBy('e.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
