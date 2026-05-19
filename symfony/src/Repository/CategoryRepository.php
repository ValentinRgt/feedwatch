<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
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
}
