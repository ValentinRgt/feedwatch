<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\PeriodicityEnum;
use App\Tests\Support\UnitTester;
use Codeception\Stub;
use DateTimeImmutable;
use Symfony\Contracts\Translation\TranslatorInterface;

class PeriodicityEnumCest
{
    public function intervalMapsEachCaseToTheExpectedDuration(UnitTester $I): void
    {
        $reference = new DateTimeImmutable('2026-01-01 00:00:00');
        $expected = [
            PeriodicityEnum::EVERY_15_MINUTES->value => '2026-01-01 00:15:00',
            PeriodicityEnum::EVERY_30_MINUTES->value => '2026-01-01 00:30:00',
            PeriodicityEnum::HOURLY->value => '2026-01-01 01:00:00',
            PeriodicityEnum::EVERY_6_HOURS->value => '2026-01-01 06:00:00',
            PeriodicityEnum::EVERY_12_HOURS->value => '2026-01-01 12:00:00',
            PeriodicityEnum::DAILY->value => '2026-01-02 00:00:00',
            PeriodicityEnum::WEEKLY->value => '2026-01-08 00:00:00',
            PeriodicityEnum::MONTHLY->value => '2026-02-01 00:00:00',
        ];

        foreach (PeriodicityEnum::cases() as $periodicity) {
            $result = $reference->add($periodicity->interval())->format('Y-m-d H:i:s');

            $I->assertSame(
                $expected[$periodicity->value],
                $result,
                sprintf('Unexpected interval for "%s".', $periodicity->value),
            );
        }
    }

    public function transBuildsThePeriodicityTranslationKey(UnitTester $I): void
    {
        /** @var TranslatorInterface $translator */
        $translator = Stub::makeEmpty(TranslatorInterface::class, [
            'trans' => fn (string $id): string => $id,
        ]);

        foreach (PeriodicityEnum::cases() as $periodicity) {
            $I->assertSame('periodicity.' . $periodicity->value, $periodicity->trans($translator));
        }
    }
}
