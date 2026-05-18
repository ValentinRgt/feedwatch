<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Support\FunctionalTester;

class HomeControllerCest
{
    public function homePageIsPubliclyAccessible(FunctionalTester $I): void
    {
        $I->amOnPage('/');

        $I->seeResponseCodeIsSuccessful();
        $I->seeCurrentRouteIs('app.home');
    }

    public function homePageRendersTheIndexTemplate(FunctionalTester $I): void
    {
        $I->amOnPage('/');

        $I->seeInTitle('Hello HomeController!');
    }

    public function anonymousUserSeesTheLoginLink(FunctionalTester $I): void
    {
        $I->amOnPage('/');

        $I->dontSeeAuthentication();
        $I->seeElement('a[href="/login"]');
    }
}
