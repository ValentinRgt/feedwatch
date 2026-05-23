<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * @param Category $category
     * @param bool $flush
     * @return void
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    public function save(Category $category, bool $flush = false): void
    {
        $this->getEntityManager()->persist($category);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param Category $category
     * @param bool $flush
     * @return void
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    public function remove(Category $category, bool $flush = false): void
    {
        $this->getEntityManager()->remove($category);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return array<int, array{id: int, name: string, articleCount: int}>
     * @SuppressWarnings("PHPMD.StaticAccess")
     */
    public function findMostActive(int $days, int $limit): array
    {
        $since = (new DateTimeImmutable())->modify('-' . $days . ' days');

        return $this->createQueryBuilder('c')
            ->select('c.id', 'c.name', 'COUNT(a.id) AS articleCount')
            ->innerJoin('c.sources', 's')
            ->innerJoin('s.articles', 'a')
            ->where('a.createdAt >= :since')
            ->setParameter('since', $since)
            ->groupBy('c.id')
            ->orderBy('articleCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }
}
