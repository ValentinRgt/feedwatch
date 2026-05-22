<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\FormatEnum;
use App\Tests\Support\UnitTester;
use Codeception\Stub;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormatEnumCest
{
    public function exposesTheExpectedCases(UnitTester $I): void
    {
        $I->assertSame('xml', FormatEnum::XML->value);
        $I->assertSame('html', FormatEnum::HTML->value);
    }

    public function transBuildsTheFormatTranslationKey(UnitTester $I): void
    {
        /** @var TranslatorInterface $translator */
        $translator = Stub::makeEmpty(TranslatorInterface::class, [
            'trans' => fn (string $id): string => $id,
        ]);

        $I->assertSame('format.xml', FormatEnum::XML->trans($translator));
        $I->assertSame('format.html', FormatEnum::HTML->trans($translator));
    }
}
