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
use RuntimeException;
use Throwable;

/**
 * The handler is exercised with every collaborator doubled, so no network
 * call or database access is involved: the reader's read() result (or thrown
 * exception) is fully scripted per source.
 *
 * Checksum gating lives entirely in the feed reader (it returns null when the
 * content has not changed): the handler only has two branches — null skips,
 * non-null updates and creates articles — plus the catch-all failure branch.
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
     * @param array<string, array{checksum: string, items: array<int, ArticleDTO>}|Throwable|null> $responses
     *        Per-source-name read() result. A Throwable is rethrown to exercise the failure branch.
     * @param list<Source> $updated Reference collecting updateSource() calls.
     * @param list<array{items: array<int, ArticleDTO>, source: Source}> $created
     *        Reference collecting createArticlesFromContent() calls.
     * @param list<array{source: Source, throwable: Throwable}> $failures
     *        Reference collecting recordFailure() calls.
     */
    private function handler(
        array $sources,
        array $responses,
        array &$updated,
        array &$created,
        array &$failures,
    ): SourceMessageHandler {
        /** @var FeedReaderInterface $reader */
        $reader = Stub::makeEmpty(FeedReaderInterface::class, [
            'read' => function (Source $source) use ($responses): ?array {
                $response = $responses[$source->getName()] ?? null;
                if ($response instanceof Throwable) {
                    throw $response;
                }

                return $response;
            },
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
            'recordFailure' => function (Source $source, Throwable $throwable) use (&$failures): void {
                $failures[] = ['source' => $source, 'throwable' => $throwable];
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
        $failures = [];
        $source = $this->source('idle');

        $handler = $this->handler([$source], ['idle' => null], $updated, $created, $failures);
        $handler(new SourceMessage());

        $I->assertEmpty($updated, 'A source without new content must not be updated.');
        $I->assertEmpty($created, 'No article should be created without new content.');
        $I->assertEmpty($failures, 'A null read is a normal skip, not a failure.');
    }

    public function updatesSourceAndCreatesArticlesForFreshContent(UnitTester $I): void
    {
        $updated = [];
        $created = [];
        $failures = [];
        $source = $this->source('fresh', 'old-checksum');
        $items = [new ArticleDTO(), new ArticleDTO()];

        $handler = $this->handler(
            [$source],
            ['fresh' => ['checksum' => 'new-checksum', 'items' => $items]],
            $updated,
            $created,
            $failures,
        );
        $handler(new SourceMessage());

        $I->assertSame('new-checksum', $source->getChecksum());
        $I->assertInstanceOf(DateTimeImmutable::class, $source->getLastFetchedAt());
        $I->assertSame([$source], $updated);
        $I->assertCount(1, $created);
        $I->assertSame($items, $created[0]['items']);
        $I->assertSame($source, $created[0]['source']);
        $I->assertEmpty($failures);
    }

    public function processesEachDueSourceIndependently(UnitTester $I): void
    {
        $updated = [];
        $created = [];
        $failures = [];
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
            $failures,
        );
        $handler(new SourceMessage());

        $I->assertSame([$fresh], $updated);
        $I->assertCount(1, $created);
        $I->assertSame($fresh, $created[0]['source']);
        $I->assertEmpty($failures);
    }

    public function recordsAFailureWhenTheReaderThrows(UnitTester $I): void
    {
        $updated = [];
        $created = [];
        $failures = [];
        $source = $this->source('broken', 'previous');
        $throwable = new RuntimeException('Reader exploded');

        $handler = $this->handler(
            [$source],
            ['broken' => $throwable],
            $updated,
            $created,
            $failures,
        );
        $handler(new SourceMessage());

        $I->assertEmpty($updated, 'A failing source must not be updated.');
        $I->assertEmpty($created, 'No article should be created when the read fails.');
        $I->assertCount(1, $failures);
        $I->assertSame($source, $failures[0]['source']);
        $I->assertSame($throwable, $failures[0]['throwable']);
    }

    public function continuesProcessingAfterAFailureOnAPreviousSource(UnitTester $I): void
    {
        $updated = [];
        $created = [];
        $failures = [];
        $broken = $this->source('broken');
        $fresh = $this->source('fresh', 'old');

        $handler = $this->handler(
            [$broken, $fresh],
            [
                'broken' => new RuntimeException('Boom'),
                'fresh' => ['checksum' => 'new', 'items' => [new ArticleDTO()]],
            ],
            $updated,
            $created,
            $failures,
        );
        $handler(new SourceMessage());

        $I->assertCount(1, $failures, 'The first source must be recorded as failed.');
        $I->assertSame($broken, $failures[0]['source']);
        $I->assertSame([$fresh], $updated, 'Sources after a failure are still processed.');
        $I->assertCount(1, $created);
        $I->assertSame($fresh, $created[0]['source']);
    }
}
