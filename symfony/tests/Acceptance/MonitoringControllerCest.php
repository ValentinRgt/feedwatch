<?php

declare(strict_types=1);

namespace App\Tests\Acceptance;

use App\Fixture\Test\CategoryFixture;
use App\Fixture\Test\SourceErrorFixture;
use App\Fixture\Test\SourceFixture;
use App\Fixture\Test\UserFixture;
use App\Tests\Support\AcceptanceTester;
use Codeception\Util\HttpCode;

class MonitoringControllerCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loadFixtures([
            $I->grabService(UserFixture::class),
            $I->grabService(CategoryFixture::class),
            $I->grabService(SourceFixture::class),
            $I->grabService(SourceErrorFixture::class),
        ]);
    }

    /**
     * An anonymous visitor is redirected to the login page.
     * @param AcceptanceTester $I
     * @return void
     */
    public function anonymousUserIsRedirectedToLogin(AcceptanceTester $I): void
    {
        $I->amOnPage('/admin/monitoring');

        $I->seeInCurrentUrl('/login');
        $I->see('Log in');
    }

    /**
     * A logged-in but non-admin user is forbidden from the monitoring page.
     * @param AcceptanceTester $I
     * @return void
     */
    public function regularUserCannotAccessTheMonitoringPage(AcceptanceTester $I): void
    {
        $I->loginAsAUser(UserFixture::USER_EMAIL);

        $I->amOnPage('/admin/monitoring');

        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    /**
     * An administrator reaches the listing and sees the seeded fetch error
     * for the "Invalid Source" entry from Fixture\Data\SourceFixture.
     * @param AcceptanceTester $I
     * @return void
     */
    public function adminCanAccessTheMonitoringListing(AcceptanceTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/monitoring');

        $I->seeResponseCodeIsSuccessful();
        $I->see('Source fetch errors');
        $I->see('Invalid Source');
        $I->dontSee('No errors recorded. All sources are running smoothly.');
    }

    /**
     * Each row exposes a GitHub "report issue" button linking to the configured repo.
     * @param AcceptanceTester $I
     * @return void
     */
    public function eachErrorRowExposesAGithubReportLink(AcceptanceTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/monitoring');

        $I->seeResponseCodeIsSuccessful();
        $I->seeElement(
            '//tbody//tr//a[contains(@href, "github.com") and contains(@href, "issues/new")]'
        );
    }

    /**
     * The source name in a row links back to the source edit page.
     * @param AcceptanceTester $I
     * @return void
     */
    public function clickingASourceNameOpensItsEditPage(AcceptanceTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/monitoring');

        $I->seeResponseCodeIsSuccessful();
        $I->click(
            '//tbody//tr//th//a[normalize-space()="Invalid Source"]'
        );
        $I->seeInCurrentUrl('/admin/source/');
        $I->seeInCurrentUrl('/edit');
    }
}

