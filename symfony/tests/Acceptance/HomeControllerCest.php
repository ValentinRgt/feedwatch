<?php

declare(strict_types=1);

namespace App\Tests\Acceptance;

use App\Tests\Support\AcceptanceTester;

class HomeControllerCest
{
    /**
     * Test that the home page is reachable and returns a successful response.
     * @param AcceptanceTester $I
     * @return void
     */
    public function homePageIsReachable(AcceptanceTester $I): void
    {
        $I->amOnPage('/');

        $I->seeResponseCodeIsSuccessful();
        $I->seeCurrentUrlEquals('/');
    }

    /**
     * Test that the home page contains a link to the login page, indicating that it exposes the login entry point.
     * @param AcceptanceTester $I
     * @return void
     */
    public function homePageExposesTheLoginEntryPoint(AcceptanceTester $I): void
    {
        $I->amOnPage('/');

        $I->seeElement('a[href="/login"]');
    }
}
