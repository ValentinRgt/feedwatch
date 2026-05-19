<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Category;
use App\Fixture\Test\CategoryFixture;
use App\Fixture\Test\UserFixture;
use App\Tests\Support\FunctionalTester;
use Codeception\Util\HttpCode;

class CategoryControllerCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->loadFixtures([
            $I->grabService(UserFixture::class),
            $I->grabService(CategoryFixture::class),
        ]);
    }

    /**
     * An anonymous visitor must be redirected to the login page.
     * @param FunctionalTester $I
     * @return void
     */
    public function anonymousUserIsRedirectedToLogin(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/category');

        $I->seeCurrentRouteIs('app.login');
    }

    /**
     * A regular (non-admin) user must be denied access.
     * @param FunctionalTester $I
     * @return void
     */
    public function regularUserIsDeniedAccess(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::USER_EMAIL);

        $I->amOnPage('/admin/category');

        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    /**
     * An administrator can reach the listing and sees the existing categories.
     * @param FunctionalTester $I
     * @return void
     */
    public function adminCanAccessTheCategoryListing(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/category');

        $I->seeResponseCodeIsSuccessful();
        $I->seeCurrentRouteIs('app.admin.category.index');
        $I->see('Category Management');
        $I->see('TEST Category 0');
    }

    /**
     * Submitting the creation form with valid data persists the category
     * and redirects back to the listing.
     * @param FunctionalTester $I
     * @return void
     */
    public function adminCanCreateACategoryWithValidData(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/category');
        $I->submitForm('form.space-y-5', [
            'category[name]' => 'Brand New Category',
        ]);

        $I->seeCurrentRouteIs('app.admin.category.index');
        $I->seeInRepository(Category::class, ['name' => 'Brand New Category']);
    }

    /**
     * Submitting the creation form with a blank name re-renders the form
     * with a validation error and persists nothing.
     * @param FunctionalTester $I
     * @return void
     */
    public function creatingACategoryFailsWithABlankName(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/category');
        $I->submitForm('form.space-y-5', [
            'category[name]' => '   ',
        ]);

        $I->seeResponseCodeIsSuccessful();
        $I->seeCurrentRouteIs('app.admin.category.index');
        $I->dontSeeInRepository(Category::class, ['name' => '   ']);
        $I->see('This value should not be blank.');
    }

    /**
     * Editing a category with valid data updates it and flashes a success message.
     * @param FunctionalTester $I
     * @return void
     */
    public function adminCanEditACategoryWithValidData(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $category = $I->grabEntityFromRepository(Category::class, ['name' => 'TEST Category 0']);

        $I->amOnPage('/admin/category/' . $category->getId() . '/edit');
        $I->seeResponseCodeIsSuccessful();

        $I->submitForm('form', [
            'category[name]' => 'TEST Category 0 (edited)',
        ]);

        $I->seeCurrentRouteIs('app.admin.category.index');
        $I->see('Category updated successfully!');
        $I->seeInRepository(Category::class, ['name' => 'TEST Category 0 (edited)']);
    }

    /**
     * Editing a category with a blank name re-renders the form with an error.
     * @param FunctionalTester $I
     * @return void
     */
    public function editingACategoryFailsWithABlankName(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $category = $I->grabEntityFromRepository(Category::class, ['name' => 'TEST Category 1']);

        $I->amOnPage('/admin/category/' . $category->getId() . '/edit');
        $I->submitForm('form', [
            'category[name]' => '',
        ]);

        $I->seeResponseCodeIsSuccessful();
        $I->seeCurrentRouteIs('app.admin.category.edit');
        $I->see('This value should not be blank.');
        $I->dontSee('Category updated successfully!');
    }

    /**
     * A regular user cannot reach the edit page either.
     * @param FunctionalTester $I
     * @return void
     */
    public function regularUserIsDeniedAccessToEdit(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::USER_EMAIL);

        $category = $I->grabEntityFromRepository(Category::class, ['name' => 'TEST Category 2']);

        $I->amOnPage('/admin/category/' . $category->getId() . '/edit');

        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }
}
