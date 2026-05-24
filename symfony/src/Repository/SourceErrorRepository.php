<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SourceError;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
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
     * @return Query
     */
    public function findSourceErrors(): Query
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery();
    }
}
