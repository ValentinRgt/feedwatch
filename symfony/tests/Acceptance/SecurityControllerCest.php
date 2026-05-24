<?php

declare(strict_types=1);

namespace App\Tests\Acceptance;

use App\Fixture\Test\UserFixture;
use App\Tests\Support\AcceptanceTester;

class SecurityControllerCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loadFixtures([
            $I->grabService(UserFixture::class),
        ]);
    }

    /**
     * Test that the login page is displayed correctly.
     * @param AcceptanceTester $I
     * @return void
     */
    public function loginPageIsDisplayed(AcceptanceTester $I): void
    {
        $I->amOnPage('/login');

        $I->seeResponseCodeIsSuccessful();
        $I->see('Log in');
        $I->seeElement('input[name="_username"]');
        $I->seeElement('input[name="_password"]');
        $I->seeElement('input[name="_csrf_token"]');
    }

    /**
     * Test that a user cannot log in with invalid credentials.
     * @param AcceptanceTester $I
     * @return void
     */
    public function loginFailsWithInvalidCredentials(AcceptanceTester $I): void
    {
        $I->amOnPage('/login');
        $I->submitForm('form', [
            '_username' => UserFixture::ADMIN_EMAIL,
            '_password' => 'wrong-password',
        ]);

        $I->seeInCurrentUrl('/login');
        $I->see('Invalid credentials.');
    }

    /**
     * Test that a user can log in and log out successfully.
     * @param AcceptanceTester $I
     * @return void
     */
    public function userCanLogInAndOut(AcceptanceTester $I): void
    {
        $I->amOnPage('/login');
        $I->submitForm('form', [
            '_username' => UserFixture::ADMIN_EMAIL,
            '_password' => UserFixture::PASSWORD,
        ]);

        $I->seeCurrentUrlEquals('/');
        $I->see(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/logout');

        $I->seeCurrentUrlEquals('/');
        $I->dontSee(UserFixture::ADMIN_EMAIL);
    }
}
