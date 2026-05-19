<?php

declare(strict_types=1);

namespace App\Tests\Acceptance;

use App\Fixture\Test\UserFixture;
use App\Tests\Support\AcceptanceTester;
use Codeception\Util\HttpCode;

class CategoryControllerCest
{
    /**
     * An anonymous visitor is redirected to the login page.
     * @param AcceptanceTester $I
     * @return void
     */
    public function anonymousUserIsRedirectedToLogin(AcceptanceTester $I): void
    {
        $I->amOnPage('/admin/category');

        $I->seeInCurrentUrl('/login');
        $I->see('Log in');
    }

    /**
     * A logged-in but non-admin user is forbidden from the admin area.
     * @param AcceptanceTester $I
     * @return void
     */
    public function regularUserCannotAccessTheCategoryAdmin(AcceptanceTester $I): void
    {
        $I->loginAsAUser(UserFixture::USER_EMAIL);

        $I->amOnPage('/admin/category');

        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    /**
     * An administrator can reach the listing and sees the seeded categories.
     * @param AcceptanceTester $I
     * @return void
     */
    public function adminCanAccessTheCategoryListing(AcceptanceTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/category');

        $I->seeResponseCodeIsSuccessful();
        $I->see('Category Management');
        $I->see('Category 0');
    }

    /**
     * Full round-trip: an admin creates a category through the form and then
     * removes it, leaving the (persistent) dev database unchanged.
     * @param AcceptanceTester $I
     * @return void
     */
    public function adminCanCreateAndRemoveACategory(AcceptanceTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/category');
        $I->submitForm('form.space-y-5', [
            'category[name]' => 'Acceptance E2E Category',
        ]);

        $I->seeInCurrentUrl('/admin/category');
        $I->see('Acceptance E2E Category');

        // Clean up so the dev DB stays in its seeded state.
        $deleteAction = $I->grabAttributeFrom(
            '//tr[th[normalize-space()="Acceptance E2E Category"]]//form[contains(@action,"/delete")]',
            'action'
        );
        $I->submitForm('form[action="' . $deleteAction . '"]', []);

        $I->seeInCurrentUrl('/admin/category');
        $I->dontSee('Acceptance E2E Category');
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

        $I->amOnPage('/admin/category');
        $I->submitForm('form.space-y-5', [
            'category[name]' => '   ',
        ]);

        $I->seeInCurrentUrl('/admin/category');
        $I->see('This value should not be blank.');
    }
}
