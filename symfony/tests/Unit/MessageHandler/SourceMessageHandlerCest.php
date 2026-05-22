<?php

declare(strict_types=1);

namespace App\Tests\Unit\MessageHandler;

use App\DTO\ArticleDTO;
use App\Entity\Source;
use App\Enum\FormatEnum;
use App\Interface\FeedReaderInterface;
use App\Message\SourceMessage;
use App\MessageHandler\SourceMessageHandler;
use App\Repository\SourceRepository;
use App\Service\ArticleService;
use App\Service\SourceService;
use App\Tests\Support\UnitTester;
use Codeception\Stub;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

/**
 * The handler is exercised with every collaborator doubled, so no network
 * call or database access is involved: the reader's read() result is fully
 * scripted per source.
 */
class SourceMessageHandlerCest
{
    private function source(string $name, ?string $checksum = null): Source
    {
        $source = (new Source())
            ->setName($name)
            ->setUrl('https://feedwatch.local/' . $name)
            ->setChecksum($checksum);
        $source->setFormat(FormatEnum::XML);

        return $source;
    }

    /**
     * @param Source[] $sources Sources reported as due.
     * @param array<string, array{checksum: string, items: array<int, ArticleDTO>}|null> $responses
     *        Per-source-name read() result.
     * @param list<Source> $updated Reference collecting updateSource() calls.
     * @param list<array{items: array<int, ArticleDTO>, source: Source}> $created
     *        Reference collecting createArticlesFromContent() calls.
     */
    private function handler(
        array $sources,
        array $responses,
        array &$updated,
        array &$created,
    ): SourceMessageHandler {
        /** @var FeedReaderInterface $reader */
        $reader = Stub::makeEmpty(FeedReaderInterface::class, [
            'read' => fn (Source $source): ?array => $responses[$source->getName()] ?? null,
        ]);

        /** @var SourceRepository $repository */
        $repository = Stub::makeEmpty(SourceRepository::class, [
            'findDueSources' => fn (): array => $sources,
        ]);

        /** @var SourceService $sourceService */
        $sourceService = Stub::makeEmpty(SourceService::class, [
            'getReader' => fn (FormatEnum $format): FeedReaderInterface => $reader,
            'updateSource' => function (Source $source) use (&$updated): void {
                $updated[] = $source;
            },
        ]);

        /** @var ArticleService $articleService */
        $articleService = Stub::makeEmpty(ArticleService::class, [
            'createArticlesFromContent' => function (array $items, Source $source) use (&$created): void {
                $created[] = ['items' => $items, 'source' => $source];
            },
        ]);

        /** @var LoggerInterface $logger */
        $logger = Stub::makeEmpty(LoggerInterface::class);

        return new SourceMessageHandler($repository, $sourceService, $articleService, $logger);
    }

    public function skipsSourcesWhenTheReaderReturnsNull(UnitTester $I): void
    {
        $updated = [];
        $created = [];
        $source = $this->source('idle');

        $handler = $this->handler([$source], ['idle' => null], $updated, $created);
        $handler(new SourceMessage());

        $I->assertEmpty($updated, 'A source without new content must not be updated.');
        $I->assertEmpty($created, 'No article should be created without new content.');
    }

    public function skipsSourcesWhoseChecksumHasNotChanged(UnitTester $I): void
    {
        $updated = [];
        $created = [];
        $source = $this->source('stable', 'same-checksum');

        $handler = $this->handler(
            [$source],
            ['stable' => ['checksum' => 'same-checksum', 'items' => [new ArticleDTO()]]],
            $updated,
            $created,
        );
        $handler(new SourceMessage());

        $I->assertEmpty($updated);
        $I->assertEmpty($created);
    }

    public function updatesSourceAndCreatesArticlesForFreshContent(UnitTester $I): void
    {
        $updated = [];
        $created = [];
        $source = $this->source('fresh', 'old-checksum');
        $items = [new ArticleDTO(), new ArticleDTO()];

        $handler = $this->handler(
            [$source],
            ['fresh' => ['checksum' => 'new-checksum', 'items' => $items]],
            $updated,
            $created,
        );
        $handler(new SourceMessage());

        $I->assertSame('new-checksum', $source->getChecksum());
        $I->assertInstanceOf(DateTimeImmutable::class, $source->getLastFetchedAt());
        $I->assertSame([$source], $updated);
        $I->assertCount(1, $created);
        $I->assertSame($items, $created[0]['items']);
        $I->assertSame($source, $created[0]['source']);
    }

    public function processesEachDueSourceIndependently(UnitTester $I): void
    {
        $updated = [];
        $created = [];
        $idle = $this->source('idle');
        $fresh = $this->source('fresh', 'old');

        $handler = $this->handler(
            [$idle, $fresh],
            [
                'idle' => null,
                'fresh' => ['checksum' => 'new', 'items' => [new ArticleDTO()]],
            ],
            $updated,
            $created,
        );
        $handler(new SourceMessage());

        $I->assertSame([$fresh], $updated);
        $I->assertCount(1, $created);
        $I->assertSame($fresh, $created[0]['source']);
    }
}
