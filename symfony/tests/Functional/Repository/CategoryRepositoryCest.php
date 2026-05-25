<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Tests\Support\FunctionalTester;

/**
 * Exercises the case-insensitive search on Category::name.
 */
class CategoryRepositoryCest
{
    private function createCategory(FunctionalTester $I, string $name): int
    {
        return $I->haveInRepository(Category::class, ['name' => $name, 'slug' => $name]);
    }

    private function repository(FunctionalTester $I): CategoryRepository
    {
        /** @var CategoryRepository $repository */
        $repository = $I->grabService(CategoryRepository::class);

        return $repository;
    }

    public function findByQueryMatchesCategoryNameCaseInsensitively(FunctionalTester $I): void
    {
        $this->createCategory($I, 'Technology');
        $this->createCategory($I, 'Sport');
        $this->createCategory($I, 'Travel');

        /** @var Category[] $lower */
        $lower = $this->repository($I)->findByQuery('technology')->getResult();
        $I->assertSame(['Technology'], array_map(static fn (Category $c): string => $c->getName(), $lower));

        /** @var Category[] $padded */
        $padded = $this->repository($I)->findByQuery('  SPORT  ')->getResult();
        $I->assertSame(['Sport'], array_map(static fn (Category $c): string => $c->getName(), $padded));
    }

    public function findByQueryReturnsEveryCategoryWhoseNameMatches(FunctionalTester $I): void
    {
        $this->createCategory($I, 'Daily News');
        $this->createCategory($I, 'News at Ten');
        $this->createCategory($I, 'Weather Watch');

        /** @var Category[] $matches */
        $matches = $this->repository($I)->findByQuery('news')->getResult();
        $names = array_map(static fn (Category $c): string => $c->getName(), $matches);
        sort($names);

        $I->assertSame(['Daily News', 'News at Ten'], $names);
    }

    public function findByQueryReturnsAnEmptyResultWhenNothingMatches(FunctionalTester $I): void
    {
        $this->createCategory($I, 'Technology');

        $I->assertSame([], $this->repository($I)->findByQuery('does-not-exist')->getResult());
    }
}
