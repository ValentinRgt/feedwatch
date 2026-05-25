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

    /**
     * The fixture seeds 20 categories; adding 5 more brings the total to 25 — with
     * the default page size (20), the listing must show 20 rows and a pagination block.
     * @param FunctionalTester $I
     * @return void
     */
    public function listingShowsTwentyItemsAndRendersPaginationByDefault(FunctionalTester $I): void
    {
        $this->seedExtraCategories($I, 5);

        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/category');

        $I->seeResponseCodeIsSuccessful();
        $rows = $I->grabMultiple('//tbody/tr');
        $I->assertCount(20, $rows, 'The default page must contain exactly the first twenty categories.');
        $I->seeElement('#pagination');
        $I->seeElement('//a[@rel="next"]');
    }

    /**
     * Navigating to ?page=2 shows the remaining categories beyond the first 20.
     * @param FunctionalTester $I
     * @return void
     */
    public function secondPageShowsTheRemainingItems(FunctionalTester $I): void
    {
        // Fixture's 20 categories + 5 extras = 25 total → page 2 holds 5 rows.
        $this->seedExtraCategories($I, 5);

        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/category?page=2');

        $I->seeResponseCodeIsSuccessful();
        $rows = $I->grabMultiple('//tbody/tr');
        $I->assertCount(5, $rows, 'The second page must contain the remaining five categories.');
        $I->seeElement('//a[@rel="prev"]');
        $I->dontSeeElement('//a[@rel="next"]');
    }

    /**
     * The pageSize query parameter overrides the default and changes the row count.
     * @param FunctionalTester $I
     * @return void
     */
    public function pageSizeQueryParameterControlsTheNumberOfRows(FunctionalTester $I): void
    {
        // 20 fixture categories + 30 extras = 50 — perfectly fills a pageSize=50 page.
        $this->seedExtraCategories($I, 30);

        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/category?pageSize=50');

        $I->seeResponseCodeIsSuccessful();
        $rows = $I->grabMultiple('//tbody/tr');
        $I->assertCount(50, $rows, 'A pageSize of 50 must fit every seeded category on the first page.');
        $I->dontSeeElement('#pagination');
    }

    /**
     * An out-of-whitelist pageSize value falls back to the first option (20 rows).
     * @param FunctionalTester $I
     * @return void
     */
    public function pageSizeFallsBackToTheDefaultWhenTheValueIsNotAllowed(FunctionalTester $I): void
    {
        $this->seedExtraCategories($I, 5);

        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/category?pageSize=25');

        $I->seeResponseCodeIsSuccessful();
        $rows = $I->grabMultiple('//tbody/tr');
        $I->assertCount(20, $rows, 'A pageSize outside the whitelist must fall back to the default (20).');
    }

    /**
     * The page-size selector renders every option from %items_per_page% and pre-selects
     * the current value (the default when no query string is provided).
     * @param FunctionalTester $I
     * @return void
     */
    public function pageSizeSelectorExposesEveryWhitelistedOption(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/category');

        $I->seeResponseCodeIsSuccessful();
        $values = $I->grabMultiple('//select[@id="page-size-select"]/option', 'value');
        $I->assertSame(['20', '50', '100'], $values);
        $I->seeElement('//select[@id="page-size-select"]/option[@value="20"][@selected]');
    }

    /**
     * Selecting a non-default page size keeps it pre-selected on the rendered listing.
     * @param FunctionalTester $I
     * @return void
     */
    public function pageSizeSelectorReflectsTheRequestedValue(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/category?pageSize=50');

        $I->seeResponseCodeIsSuccessful();
        $I->seeElement('//select[@id="page-size-select"]/option[@value="50"][@selected]');
        $I->dontSeeElement('//select[@id="page-size-select"]/option[@value="20"][@selected]');
    }

    public function searchQueryFiltersCategoriesByName(FunctionalTester $I): void
    {
        $I->haveInRepository(Category::class, ['name' => 'Technology']);
        $I->haveInRepository(Category::class, ['name' => 'Sport']);

        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/category?q=technology');

        $I->seeResponseCodeIsSuccessful();
        $rows = $I->grabMultiple('//tbody/tr');
        $I->assertCount(1, $rows);
        $I->see('Technology');
        $I->dontSee('Sport');
        $I->dontSee('TEST Category 0');
    }

    public function searchQueryEchoesIntoTheSearchInput(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/category?q=technology');

        $I->seeResponseCodeIsSuccessful();
        $I->seeElement('//input[@id="search-query"][@name="q"][@value="technology"]');
    }

    private function seedExtraCategories(FunctionalTester $I, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $I->haveInRepository(Category::class, [
                'name' => 'Pagination Category ' . $i,
            ]);
        }
    }
}
