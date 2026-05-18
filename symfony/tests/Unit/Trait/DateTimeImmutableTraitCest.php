<?php

declare(strict_types=1);

namespace App\Tests\Unit\Trait;

use App\Tests\Support\UnitTester;
use App\Trait\DateTimeImmutableTrait;
use DateTimeImmutable;

class DateTimeImmutableTraitCest
{
    /**
     * @return object Anonymous class using the trait under test.
     */
    private function subject(): object
    {
        return new class {
            use DateTimeImmutableTrait;
        };
    }

    public function timestampsAreNullByDefault(UnitTester $I): void
    {
        $subject = $this->subject();

        $I->assertNull($subject->getCreatedAt());
        $I->assertNull($subject->getUpdatedAt());
    }

    public function createdAtCanBeSetAndRead(UnitTester $I): void
    {
        $subject = $this->subject();
        $date = new DateTimeImmutable('2026-05-18 09:30:00');

        $result = $subject->setCreatedAt($date);

        $I->assertSame($subject, $result);
        $I->assertSame($date, $subject->getCreatedAt());
    }

    public function updatedAtCanBeSetAndRead(UnitTester $I): void
    {
        $subject = $this->subject();
        $date = new DateTimeImmutable('2026-05-18 15:45:00');

        $result = $subject->setUpdatedAt($date);

        $I->assertSame($subject, $result);
        $I->assertSame($date, $subject->getUpdatedAt());
    }
}
