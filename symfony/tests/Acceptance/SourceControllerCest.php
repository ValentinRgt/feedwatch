<?php

declare(strict_types=1);

namespace App\Tests\Acceptance;

use App\Fixture\Test\CategoryFixture;
use App\Fixture\Test\SourceFixture;
use App\Fixture\Test\UserFixture;
use App\Tests\Support\AcceptanceTester;
use Codeception\Util\HttpCode;

class SourceControllerCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loadFixtures([
            $I->grabService(UserFixture::class),
            $I->grabService(CategoryFixture::class),
            $I->grabService(SourceFixture::class),
        ]);
    }

    /**
     * An anonymous visitor is redirected to the login page.
     * @param AcceptanceTester $I
     * @return void
     */
    public function anonymousUserIsRedirectedToLogin(AcceptanceTester $I): void
    {
        $I->amOnPage('/admin/source');

        $I->seeInCurrentUrl('/login');
        $I->see('Log in');
    }

    /**
     * A logged-in but non-admin user is forbidden from the admin area.
     * @param AcceptanceTester $I
     * @return void
     */
    public function regularUserCannotAccessTheSourceAdmin(AcceptanceTester $I): void
    {
        $I->loginAsAUser(UserFixture::USER_EMAIL);

        $I->amOnPage('/admin/source');

        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    /**
     * An administrator can reach the listing and sees the seeded sources.
     * @param AcceptanceTester $I
     * @return void
     */
    public function adminCanAccessTheSourceListing(AcceptanceTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/source');

        $I->seeResponseCodeIsSuccessful();
        $I->see('Source Management');
        $I->see('TEST Source 0');
    }

    /**
     * Full round-trip: an admin creates a source through the form and then
     * removes it, leaving the (persistent) dev database unchanged.
     * @param AcceptanceTester $I
     * @return void
     */
    public function adminCanCreateAndRemoveASource(AcceptanceTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/source');
        $I->submitForm('form.grid', [
            'source[name]' => 'Acceptance E2E Source',
            'source[url]' => 'https://feedwatch.local/acceptance-e2e',
            'source[format]' => 'xml',
            'source[status]' => 'active',
            'source[periodicity]' => 'hourly',
        ]);

        $I->seeInCurrentUrl('/admin/source');
        $I->see('Acceptance E2E Source');

        // Clean up so the dev DB stays in its seeded state.
        $deleteAction = $I->grabAttributeFrom(
            '//tr[th[normalize-space()="Acceptance E2E Source"]]//form[contains(@action,"/delete")]',
            'action'
        );
        $I->submitForm('form[action="' . $deleteAction . '"]', []);

        $I->seeInCurrentUrl('/admin/source');
        $I->dontSee('Acceptance E2E Source');
    }

    /**
     * Submitting the creation form with a blank name shows a validation error
     * and does not navigate away (nothing is persisted).
     * @param AcceptanceTester $I
     * @return void
     */
    public function submittingTheCreateFormWithABlankNameShowsAValidationError(AcceptanceTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/source');
        $I->submitForm('form.grid', [
            'source[name]' => '   ',
            'source[url]' => 'https://feedwatch.local/acceptance-blank',
            'source[format]' => 'xml',
            'source[status]' => 'active',
            'source[periodicity]' => 'hourly',
        ]);

        $I->seeInCurrentUrl('/admin/source');
        $I->see('This value should not be blank.');
    }

    /**
     * Round-trip on an HTML source: a fully configured HTML source (with XPath
     * item selectors) is created, surfaced in the listing, then deleted to keep
     * the dev DB clean.
     * @param AcceptanceTester $I
     * @return void
     */
    public function adminCanCreateAndRemoveAnHtmlSourceWithItemSelectors(AcceptanceTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/source');
        $I->submitForm('form.grid', [
            'source[name]' => 'Acceptance HTML Source',
            'source[url]' => 'https://feedwatch.local/acceptance-html',
            'source[format]' => 'html',
            'source[status]' => 'active',
            'source[periodicity]' => 'hourly',
            'source[itemContainer]' => '//article[@class="card"]',
            'source[itemTitle]' => './/h2',
            'source[itemLink]' => './/a/@href',
            'source[itemPublishedAt]' => './/time/@datetime',
        ]);

        $I->seeInCurrentUrl('/admin/source');
        $I->see('Acceptance HTML Source');

        // Clean up so the dev DB stays in its seeded state.
        $deleteAction = $I->grabAttributeFrom(
            '//tr[th[normalize-space()="Acceptance HTML Source"]]//form[contains(@action,"/delete")]',
            'action'
        );
        $I->submitForm('form[action="' . $deleteAction . '"]', []);

        $I->seeInCurrentUrl('/admin/source');
        $I->dontSee('Acceptance HTML Source');
    }

    /**
     * Submitting the creation form with format=html but one of the required
     * XPath selectors left blank surfaces a validation error and persists
     * nothing.
     * @param AcceptanceTester $I
     * @return void
     */
    public function submittingTheHtmlCreateFormWithoutRequiredSelectorsShowsAValidationError(
        AcceptanceTester $I
    ): void {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/source');
        $I->submitForm('form.grid', [
            'source[name]' => 'Acceptance HTML Missing Selectors',
            'source[url]' => 'https://feedwatch.local/acceptance-html-missing',
            'source[format]' => 'html',
            'source[status]' => 'active',
            'source[periodicity]' => 'hourly',
            'source[itemContainer]' => '',
            'source[itemTitle]' => '',
            'source[itemLink]' => '',
        ]);

        $I->seeInCurrentUrl('/admin/source');
        $I->see('This value should not be blank.');
        $I->dontSee('Acceptance HTML Missing Selectors');
    }
}
