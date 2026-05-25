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
     * @param string|null $search
     * @return Query
     */
    public function findByCategoryQuery(?Category $category = null, ?string $search = null): Query
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

        if (!empty($search)) {
            $qb->andWhere('LOWER(a.title) LIKE :search OR LOWER(s.name) LIKE :search OR LOWER(c.name) LIKE :search')
                ->setParameter('search', '%' . strtolower(trim($search)) . '%');
        }

        return $qb->getQuery();
    }

    /**
     * @param string $search
     * @return Query
     */
    public function findByQuery(string $search): Query
    {
        $qb = $this->createQueryBuilder('a');
        $qb->innerJoin('a.source', 's')
            ->leftJoin('s.category', 'c');

        $qb->where('LOWER(a.title) LIKE :search')
            ->orWhere('LOWER(s.name) LIKE :search')
            ->orWhere('LOWER(c.name) LIKE :search')
            ->setParameter('search', '%' . strtolower(trim($search)) . '%');

        $qb->orderBy('a.publishedAt', 'DESC')
            ->addOrderBy('a.createdAt', 'DESC');

        return $qb->getQuery();
    }
}
