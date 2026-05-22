<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\StatusEnum;
use App\Tests\Support\UnitTester;
use Codeception\Stub;
use Symfony\Contracts\Translation\TranslatorInterface;

class StatusEnumCest
{
    public function exposesTheExpectedCases(UnitTester $I): void
    {
        $I->assertSame('active', StatusEnum::ACTIVE->value);
        $I->assertSame('inactive', StatusEnum::INACTIVE->value);
    }

    public function transBuildsTheStatusTranslationKey(UnitTester $I): void
    {
        /** @var TranslatorInterface $translator */
        $translator = Stub::makeEmpty(TranslatorInterface::class, [
            'trans' => fn (string $id): string => $id,
        ]);

        $I->assertSame('status.active', StatusEnum::ACTIVE->trans($translator));
        $I->assertSame('status.inactive', StatusEnum::INACTIVE->trans($translator));
    }
}
