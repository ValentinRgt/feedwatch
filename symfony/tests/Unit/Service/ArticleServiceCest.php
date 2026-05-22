<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\ArticleDTO;
use App\Entity\Article;
use App\Entity\Source;
use App\Repository\ArticleRepository;
use App\Service\ArticleService;
use App\Tests\Support\UnitTester;
use Codeception\Stub;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

class ArticleServiceCest
{
    /**
     * Object mapper double turning an ArticleDTO into an Article entity,
     * mirroring the real DTO -> entity mapping closely enough for assertions.
     */
    private function objectMapper(): ObjectMapperInterface
    {
        /** @var ObjectMapperInterface $mapper */
        $mapper = Stub::makeEmpty(ObjectMapperInterface::class, [
            'map' => fn (object $source, string|object $target = null): object => (new Article())
                ->setChecksum($source->checksum)
                ->setTitle($source->title)
                ->setLink($source->link),
        ]);

        return $mapper;
    }

    /**
     * @param string[] $existingChecksums Checksums the repository should report as already stored.
     * @param Article[] $saved Reference collecting every persisted article.
     */
    private function repository(array $existingChecksums, array &$saved): ArticleRepository
    {
        /** @var ArticleRepository $repository */
        $repository = Stub::makeEmpty(ArticleRepository::class, [
            'findExistingChecksums' => fn (array $checksums): array => array_values(
                array_intersect($checksums, $existingChecksums)
            ),
            'save' => function (Article $article, bool $flush = false) use (&$saved): void {
                $saved[] = $article;
            },
        ]);

        return $repository;
    }

    private function dto(string $checksum, string $title): ArticleDTO
    {
        $dto = new ArticleDTO();
        $dto->checksum = $checksum;
        $dto->title = $title;
        $dto->link = 'https://feedwatch.local/' . $checksum;

        return $dto;
    }

    public function persistsOnlyArticlesWithUnknownChecksums(UnitTester $I): void
    {
        $saved = [];
        $service = new ArticleService($this->repository(['dup'], $saved), $this->objectMapper());
        $source = new Source();

        $service->createArticlesFromContent(
            [$this->dto('new-1', 'First'), $this->dto('dup', 'Duplicate'), $this->dto('new-2', 'Second')],
            $source,
        );

        $I->assertCount(2, $saved);
        $checksums = array_map(fn (Article $a): ?string => $a->getChecksum(), $saved);
        $I->assertSame(['new-1', 'new-2'], $checksums);
    }

    public function persistsNothingWhenEveryChecksumAlreadyExists(UnitTester $I): void
    {
        $saved = [];
        $service = new ArticleService($this->repository(['a', 'b'], $saved), $this->objectMapper());

        $service->createArticlesFromContent(
            [$this->dto('a', 'First'), $this->dto('b', 'Second')],
            new Source(),
        );

        $I->assertEmpty($saved);
    }

    public function persistsEveryArticleWhenNoneExistYet(UnitTester $I): void
    {
        $saved = [];
        $service = new ArticleService($this->repository([], $saved), $this->objectMapper());

        $service->createArticlesFromContent(
            [$this->dto('a', 'First'), $this->dto('b', 'Second')],
            new Source(),
        );

        $I->assertCount(2, $saved);
    }

    public function assignsTheGivenSourceToEachCreatedArticle(UnitTester $I): void
    {
        $saved = [];
        $service = new ArticleService($this->repository([], $saved), $this->objectMapper());
        $source = (new Source())->setName('Tech feed');

        $service->createArticlesFromContent([$this->dto('a', 'First')], $source);

        $I->assertCount(1, $saved);
        $I->assertSame($source, $saved[0]->getSource());
    }
}
