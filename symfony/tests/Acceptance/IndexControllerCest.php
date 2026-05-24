<?php

declare(strict_types=1);

namespace App\Tests\Acceptance;

use App\Fixture\Test\UserFixture;
use App\Tests\Support\AcceptanceTester;
use Codeception\Util\HttpCode;

class IndexControllerCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loadFixtures([
            $I->grabService(UserFixture::class)
        ]);
    }

    /**
     * An anonymous visitor is redirected to the login page.
     * @param AcceptanceTester $I
     * @return void
     */
    public function anonymousUserIsRedirectedToLogin(AcceptanceTester $I): void
    {
        $I->amOnPage('/admin/');

        $I->seeInCurrentUrl('/login');
        $I->see('Log in');
    }

    /**
     * A logged-in but non-admin user is forbidden from the admin dashboard.
     * @param AcceptanceTester $I
     * @return void
     */
    public function regularUserCannotAccessTheDashboard(AcceptanceTester $I): void
    {
        $I->loginAsAUser(UserFixture::USER_EMAIL);

        $I->amOnPage('/admin/');

        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    /**
     * An administrator reaches the dashboard and sees the four stat cards.
     * @param AcceptanceTester $I
     * @return void
     */
    public function adminCanAccessTheDashboard(AcceptanceTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/');

        $I->seeResponseCodeIsSuccessful();
        $I->see('Sources');
        $I->see('Categories');
        $I->see('Feeds');
        $I->see('Errors');
    }

    /**
     * The "Errors" stat card on the dashboard is a link to the monitoring page.
     * @param AcceptanceTester $I
     * @return void
     */
    public function errorsCardLinksToTheMonitoringPage(AcceptanceTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/');

        $I->seeResponseCodeIsSuccessful();
        $I->click('//a[contains(@href, "/admin/monitoring")][.//p[normalize-space()="Errors"]]');
        $I->seeInCurrentUrl('/admin/monitoring');
    }
}
