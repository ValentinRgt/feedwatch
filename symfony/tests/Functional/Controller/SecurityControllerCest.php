<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Tests\Support\FunctionalTester;
use App\Fixture\Test\UserFixture;

class SecurityControllerCest
{
    /**
     * Load the user fixture before each test to ensure a consistent state.
     * @param FunctionalTester $I
     * @return void
     */
    public function _before(FunctionalTester $I): void
    {
        $I->loadFixtures([
            $I->grabService(UserFixture::class)
        ]);
    }

    /**
     * Test that the login page is accessible and contains the expected elements.
     * @param FunctionalTester $I
     * @return void
     */
    public function loginPageIsAccessible(FunctionalTester $I): void
    {
        $I->amOnPage('/login');

        $I->seeResponseCodeIsSuccessful();
        $I->seeInTitle('FeedWatch :: Login');
        $I->seeElement('input[name="_username"]');
        $I->seeElement('input[name="_password"]');
        $I->seeElement('input[name="_csrf_token"]');
    }

    /**
     * Test that a user cannot log in with invalid credentials.
     * @param FunctionalTester $I
     * @return void
     */
    public function loginFailsWithInvalidCredentials(FunctionalTester $I): void
    {
        $I->amOnPage('/login');
        $I->submitForm('form', [
            '_username' => UserFixture::ADMIN_EMAIL,
            '_password' => 'wrong-password',
        ]);

        $I->dontSeeAuthentication();
        $I->seeCurrentRouteIs('app.login');
        $I->see('Invalid credentials.');
    }

    /**
     * Test that a user cannot log in with an unknown email address.
     * @param FunctionalTester $I
     * @return void
     */
    public function loginFailsForUnknownUser(FunctionalTester $I): void
    {
        $I->amOnPage('/login');
        $I->submitForm('form', [
            '_username' => 'nobody@feedwatch.local',
            '_password' => UserFixture::PASSWORD,
        ]);

        $I->dontSeeAuthentication();
        $I->seeCurrentRouteIs('app.login');
    }

    /**
     * Test that a user can log in with valid credentials and is redirected to the home page.
     * @param FunctionalTester $I
     * @return void
     */
    public function loginSucceedsWithValidCredentials(FunctionalTester $I): void
    {
        $I->amOnPage('/login');
        $I->submitForm('form', [
            '_username' => UserFixture::ADMIN_EMAIL,
            '_password' => UserFixture::PASSWORD,
        ]);

        $I->seeAuthentication();
        $I->seeCurrentRouteIs('app.home');
        $I->see(UserFixture::ADMIN_EMAIL);
    }

    /**
     * Test that the logout route logs the user out and redirects to the home page.
     * @param FunctionalTester $I
     * @return void
     */
    public function logoutLogsTheUserOut(FunctionalTester $I): void
    {
        $user = $I->grabEntityFromRepository(User::class, ['email' => UserFixture::ADMIN_EMAIL]);

        $I->amLoggedInAs($user);
        $I->amOnPage('/');
        $I->seeAuthentication();

        $I->amOnPage('/logout');

        $I->dontSeeAuthentication();
        $I->seeCurrentRouteIs('app.home');
    }
}
