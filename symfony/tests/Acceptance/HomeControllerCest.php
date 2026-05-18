<?php

declare(strict_types=1);

namespace App\Tests\Acceptance;

use App\Tests\Support\AcceptanceTester;

class HomeControllerCest
{
    public function homePageIsReachable(AcceptanceTester $I): void
    {
        $I->amOnPage('/');

        $I->seeResponseCodeIsSuccessful();
        $I->seeCurrentUrlEquals('/');
    }

    public function homePageExposesTheLoginEntryPoint(AcceptanceTester $I): void
    {
        $I->amOnPage('/');

        $I->seeElement('a[href="/login"]');
    }
}
