<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Source;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Source>
 */
class SourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Source::class);
    }

    /**
     * @param Source $source
     * @param bool $flush
     * @return void
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    public function save(Source $source, bool $flush = false): void
    {
        $this->getEntityManager()->persist($source);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param Source $source
     * @param bool $flush
     * @return void
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    public function remove(Source $source, bool $flush = false): void
    {
        $this->getEntityManager()->remove($source);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
