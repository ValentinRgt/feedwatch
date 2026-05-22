<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Source;
use App\Enum\FormatEnum;
use App\Enum\PeriodicityEnum;
use App\Enum\StatusEnum;
use App\Repository\ArticleRepository;
use App\Tests\Support\FunctionalTester;
use DateTimeImmutable;

class ArticleRepositoryCest
{
    private function createSource(FunctionalTester $I, string $name, ?Category $category = null): Source
    {
        $id = $I->haveInRepository(Source::class, [
            'name' => $name,
            'url' => 'https://feedwatch.local/' . $name,
            'format' => FormatEnum::XML,
            'status' => StatusEnum::ACTIVE,
            'periodicity' => PeriodicityEnum::HOURLY,
            'category' => $category,
        ]);

        return $I->grabEntityFromRepository(Source::class, ['id' => $id]);
    }

    private function createCategory(FunctionalTester $I, string $name): Category
    {
        $id = $I->haveInRepository(Category::class, ['name' => $name, 'slug' => $name]);

        return $I->grabEntityFromRepository(Category::class, ['id' => $id]);
    }

    private function repository(FunctionalTester $I): ArticleRepository
    {
        /** @var ArticleRepository $repository */
        $repository = $I->grabService(ArticleRepository::class);

        return $repository;
    }

    public function findExistingChecksumsReturnsOnlyThePersistedOnes(FunctionalTester $I): void
    {
        $source = $this->createSource($I, 'feed');
        $I->haveInRepository(Article::class, ['title' => 'A', 'link' => 'l-a', 'checksum' => 'aaa', 'source' => $source]);
        $I->haveInRepository(Article::class, ['title' => 'B', 'link' => 'l-b', 'checksum' => 'bbb', 'source' => $source]);

        $existing = $this->repository($I)->findExistingChecksums(['aaa', 'bbb', 'missing']);

        $I->assertContains('aaa', $existing);
        $I->assertContains('bbb', $existing);
        $I->assertNotContains('missing', $existing);
    }

    public function findExistingChecksumsReturnsAnEmptyArrayWhenNothingMatches(FunctionalTester $I): void
    {
        $source = $this->createSource($I, 'feed');
        $I->haveInRepository(Article::class, ['title' => 'A', 'link' => 'l-a', 'checksum' => 'aaa', 'source' => $source]);

        $I->assertSame([], $this->repository($I)->findExistingChecksums(['none', 'nope']));
    }

    public function findByCategoryQueryOrdersArticlesByMostRecentlyPublished(FunctionalTester $I): void
    {
        $source = $this->createSource($I, 'feed');
        $I->haveInRepository(Article::class, [
            'title' => 'Older', 'link' => 'l-old', 'source' => $source,
            'publishedAt' => new DateTimeImmutable('2026-05-01 10:00:00'),
        ]);
        $I->haveInRepository(Article::class, [
            'title' => 'Newer', 'link' => 'l-new', 'source' => $source,
            'publishedAt' => new DateTimeImmutable('2026-05-20 10:00:00'),
        ]);

        /** @var Article[] $articles */
        $articles = $this->repository($I)->findByCategoryQuery()->getResult();

        $I->assertSame('Newer', $articles[0]->getTitle());
        $I->assertSame('Older', $articles[1]->getTitle());
    }

    public function findByCategoryQueryReturnsOnlyArticlesOfTheGivenCategory(FunctionalTester $I): void
    {
        $tech = $this->createCategory($I, 'tech');
        $sport = $this->createCategory($I, 'sport');
        $techSource = $this->createSource($I, 'tech-feed', $tech);
        $sportSource = $this->createSource($I, 'sport-feed', $sport);

        $I->haveInRepository(Article::class, ['title' => 'Tech news', 'link' => 'l-t', 'source' => $techSource]);
        $I->haveInRepository(Article::class, ['title' => 'Sport news', 'link' => 'l-s', 'source' => $sportSource]);

        /** @var Article[] $techArticles */
        $techArticles = $this->repository($I)->findByCategoryQuery($tech)->getResult();
        $titles = array_map(static fn (Article $a): ?string => $a->getTitle(), $techArticles);

        $I->assertSame(['Tech news'], $titles);
    }

    public function findByCategoryQueryReturnsEveryArticleWhenNoCategoryIsGiven(FunctionalTester $I): void
    {
        $tech = $this->createCategory($I, 'tech');
        $techSource = $this->createSource($I, 'tech-feed', $tech);
        $orphanSource = $this->createSource($I, 'orphan-feed');

        $I->haveInRepository(Article::class, ['title' => 'Tech news', 'link' => 'l-t', 'source' => $techSource]);
        $I->haveInRepository(Article::class, ['title' => 'Orphan news', 'link' => 'l-o', 'source' => $orphanSource]);

        /** @var Article[] $all */
        $all = $this->repository($I)->findByCategoryQuery()->getResult();

        $I->assertCount(2, $all);
    }
}
