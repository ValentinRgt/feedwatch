<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Tests\Support\UnitTester;
use App\Twig\ComposerVersion;

class ComposerVersionCest
{
    public function getVersionReturnsANonEmptyString(UnitTester $I): void
    {
        $version = (new ComposerVersion())->getVersion();

        $I->assertIsString($version);
        $I->assertNotEmpty($version);
    }

    public function toStringPrefixesTheVersionWithV(UnitTester $I): void
    {
        $composerVersion = new ComposerVersion();

        $I->assertSame('v' . $composerVersion->getVersion(), (string) $composerVersion);
    }
}
