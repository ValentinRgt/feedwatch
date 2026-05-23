<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Source;
use App\Enum\PeriodicityEnum;
use App\Enum\StatusEnum;
use DateTimeImmutable;
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

    /**
     * @return Source[]
     * @SuppressWarnings("PHPMD.StaticAccess")
     */
    public function findDueSources(): array
    {
        $now = new DateTimeImmutable();

        $qb = $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->setParameter('status', StatusEnum::ACTIVE);

        $dueByPeriodicity = $qb->expr()->orX();

        foreach (PeriodicityEnum::cases() as $periodicity) {
            $key = $periodicity->value;

            $dueByPeriodicity->add($qb->expr()->andX(
                $qb->expr()->eq('s.periodicity', ':periodicity_' . $key),
                $qb->expr()->orX(
                    $qb->expr()->isNull('s.lastFetchedAt'),
                    $qb->expr()->lte('s.lastFetchedAt', ':due_' . $key),
                ),
            ));

            $qb->setParameter('periodicity_' . $key, $periodicity)
                ->setParameter('due_' . $key, $now->sub($periodicity->interval()));
        }

        $qb->andWhere($dueByPeriodicity);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, array{id: int, name: string, articleCount: int}>
     * @SuppressWarnings("PHPMD.StaticAccess")
     */
    public function findMostActive(int $days, int $limit): array
    {
        $since = (new DateTimeImmutable())->modify('-' . $days . ' days');

        return $this->createQueryBuilder('s')
            ->select('s.id', 's.name', 'COUNT(a.id) AS articleCount')
            ->innerJoin('s.articles', 'a')
            ->where('a.createdAt >= :since')
            ->setParameter('since', $since)
            ->groupBy('s.id')
            ->orderBy('articleCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }
}
