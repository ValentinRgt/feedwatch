<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * @param Article $article
     * @param bool $flush
     * @return void
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    public function save(Article $article, bool $flush = false): void
    {
        $this->getEntityManager()->persist($article);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param Article $article
     * @param bool $flush
     * @return void
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    public function remove(Article $article, bool $flush = false): void
    {
        $this->getEntityManager()->remove($article);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param array<int, string> $checksums
     * @return array<int, string>
     */
    public function findExistingChecksums(array $checksums): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.checksum')
            ->where('a.checksum IN (:checksums)')
            ->setParameter('checksums', $checksums)
            ->getQuery()
            ->getSingleColumnResult();
    }

    /**
     * @param Category|null $category
     * @return Query
     */
    public function findByCategoryQuery(?Category $category = null): Query
    {
        $qb = $this->createQueryBuilder('a');

        $qb->innerJoin('a.source', 's')
            ->leftJoin('s.category', 'c');

        $qb->orderBy('a.publishedAt', 'DESC')
            ->addOrderBy('a.createdAt', 'DESC');

        if (null !== $category) {
            $qb->andWhere('s.category = :category')
                ->setParameter('category', $category);
        }

        return $qb->getQuery();
    }
}
