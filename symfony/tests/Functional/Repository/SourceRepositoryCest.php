<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\Source;
use App\Enum\FormatEnum;
use App\Enum\PeriodicityEnum;
use App\Enum\StatusEnum;
use App\Repository\SourceRepository;
use App\Tests\Support\FunctionalTester;
use DateTimeImmutable;

/**
 * Exercises the real findDueSources() query against the test database.
 */
class SourceRepositoryCest
{
    /**
     * @param array<string, mixed> $overrides
     */
    private function createSource(FunctionalTester $I, string $name, array $overrides = []): int
    {
        return $I->haveInRepository(Source::class, array_merge([
            'name' => $name,
            'url' => 'https://feedwatch.local/' . $name,
            'format' => FormatEnum::XML,
            'status' => StatusEnum::ACTIVE,
            'periodicity' => PeriodicityEnum::HOURLY,
            'lastFetchedAt' => null,
        ], $overrides));
    }

    /**
     * @return int[] Ids of the sources returned by findDueSources().
     */
    private function dueSourceIds(FunctionalTester $I): array
    {
        /** @var SourceRepository $repository */
        $repository = $I->grabService(SourceRepository::class);

        return array_map(
            static fn (Source $source): int => $source->getId(),
            $repository->findDueSources(),
        );
    }

    public function includesActiveSourceThatWasNeverFetched(FunctionalTester $I): void
    {
        $id = $this->createSource($I, 'never-fetched', ['lastFetchedAt' => null]);

        $I->assertContains($id, $this->dueSourceIds($I));
    }

    public function excludesInactiveSourcesEvenWhenDue(FunctionalTester $I): void
    {
        $id = $this->createSource($I, 'inactive', [
            'status' => StatusEnum::INACTIVE,
            'lastFetchedAt' => null,
        ]);

        $I->assertNotContains($id, $this->dueSourceIds($I));
    }

    public function excludesActiveSourceFetchedWithinItsPeriodicity(FunctionalTester $I): void
    {
        $id = $this->createSource($I, 'recently-fetched', [
            'periodicity' => PeriodicityEnum::HOURLY,
            'lastFetchedAt' => new DateTimeImmutable('-10 minutes'),
        ]);

        $I->assertNotContains($id, $this->dueSourceIds($I));
    }

    public function includesActiveSourceFetchedBeforeItsPeriodicityElapsed(FunctionalTester $I): void
    {
        $id = $this->createSource($I, 'stale', [
            'periodicity' => PeriodicityEnum::HOURLY,
            'lastFetchedAt' => new DateTimeImmutable('-2 hours'),
        ]);

        $I->assertContains($id, $this->dueSourceIds($I));
    }

    public function returnsOnlyTheDueActiveSourcesAmongAMixedSet(FunctionalTester $I): void
    {
        $due = $this->createSource($I, 'due', ['lastFetchedAt' => new DateTimeImmutable('-2 hours')]);
        $notDue = $this->createSource($I, 'not-due', ['lastFetchedAt' => new DateTimeImmutable('-5 minutes')]);
        $inactive = $this->createSource($I, 'off', ['status' => StatusEnum::INACTIVE]);

        $ids = $this->dueSourceIds($I);

        $I->assertContains($due, $ids);
        $I->assertNotContains($notDue, $ids);
        $I->assertNotContains($inactive, $ids);
    }

    public function findByQueryMatchesSourceNameCaseInsensitively(FunctionalTester $I): void
    {
        $this->createSource($I, 'TechCrunch');
        $this->createSource($I, 'Eurosport');
        $this->createSource($I, 'Le Monde');

        /** @var SourceRepository $repository */
        $repository = $I->grabService(SourceRepository::class);

        /** @var Source[] $lower */
        $lower = $repository->findByQuery('techcrunch')->getResult();
        $I->assertSame(['TechCrunch'], array_map(static fn (Source $s): string => $s->getName(), $lower));

        /** @var Source[] $padded */
        $padded = $repository->findByQuery('  LE MONDE  ')->getResult();
        $I->assertSame(['Le Monde'], array_map(static fn (Source $s): string => $s->getName(), $padded));
    }

    public function findByQueryReturnsEverySourceWhoseNameMatches(FunctionalTester $I): void
    {
        $this->createSource($I, 'Daily News');
        $this->createSource($I, 'News at Ten');
        $this->createSource($I, 'Weather Watch');

        /** @var SourceRepository $repository */
        $repository = $I->grabService(SourceRepository::class);

        /** @var Source[] $matches */
        $matches = $repository->findByQuery('news')->getResult();
        $names = array_map(static fn (Source $s): string => $s->getName(), $matches);
        sort($names);

        $I->assertSame(['Daily News', 'News at Ten'], $names);
    }

    public function findByQueryReturnsAnEmptyResultWhenNothingMatches(FunctionalTester $I): void
    {
        $this->createSource($I, 'TechCrunch');

        /** @var SourceRepository $repository */
        $repository = $I->grabService(SourceRepository::class);

        $I->assertSame([], $repository->findByQuery('does-not-exist')->getResult());
    }
}
