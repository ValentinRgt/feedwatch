<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Source;
use App\Enum\FormatEnum;
use App\Enum\PeriodicityEnum;
use App\Enum\StatusEnum;
use App\Fixture\Test\CategoryFixture;
use App\Fixture\Test\SourceFixture;
use App\Fixture\Test\UserFixture;
use App\Tests\Support\FunctionalTester;
use Codeception\Util\HttpCode;

class SourceControllerCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->loadFixtures([
            $I->grabService(UserFixture::class),
            $I->grabService(CategoryFixture::class),
            $I->grabService(SourceFixture::class),
        ]);
    }

    public function anonymousUserIsRedirectedToLogin(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/source');

        $I->seeCurrentRouteIs('app.login');
    }

    public function regularUserIsDeniedAccess(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::USER_EMAIL);

        $I->amOnPage('/admin/source');

        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function adminCanAccessTheSourceListing(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/source');

        $I->seeResponseCodeIsSuccessful();
        $I->seeCurrentRouteIs('app.admin.source.index');
        $I->see('Source Management');
        $I->see('TEST Source 0');
    }

    public function adminCanCreateASourceWithValidData(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/source');
        $I->submitForm('form.grid', [
            'source[name]' => 'Brand New Source',
            'source[url]' => 'https://feedwatch.local/brand-new',
            'source[format]' => 'xml',
            'source[status]' => 'active',
            'source[periodicity]' => 'hourly',
        ]);

        $I->seeCurrentRouteIs('app.admin.source.index');
        $I->seeInRepository(Source::class, ['name' => 'Brand New Source']);
    }

    public function creatingASourceFailsWithABlankName(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/source');
        $I->submitForm('form.grid', [
            'source[name]' => '   ',
            'source[url]' => 'https://feedwatch.local/blank-name',
            'source[format]' => 'xml',
            'source[status]' => 'active',
            'source[periodicity]' => 'hourly',
        ]);

        $I->seeResponseCodeIsSuccessful();
        $I->see('This value should not be blank.');
        $I->dontSeeInRepository(Source::class, ['url' => 'https://feedwatch.local/blank-name']);
    }

    public function editingASourceResetsItsFetchState(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $source = $I->grabEntityFromRepository(Source::class, ['name' => 'TEST Source 0']);
        $I->assertSame('seeded-checksum', $source->getChecksum());

        $I->amOnPage('/admin/source/' . $source->getId() . '/edit');
        $I->seeResponseCodeIsSuccessful();

        $I->submitForm('form.grid', [
            'source[name]' => 'TEST Source 0 (edited)',
            'source[url]' => 'https://feedwatch.local/feed/0',
            'source[format]' => 'xml',
            'source[status]' => 'active',
            'source[periodicity]' => 'hourly',
        ]);

        $I->seeCurrentRouteIs('app.admin.source.index');
        $I->see('Source updated successfully!');

        $updated = $I->grabEntityFromRepository(Source::class, ['name' => 'TEST Source 0 (edited)']);
        $I->assertNull($updated->getChecksum());
        $I->assertNull($updated->getLastFetchedAt());
    }

    public function regularUserIsDeniedAccessToEdit(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::USER_EMAIL);

        $source = $I->grabEntityFromRepository(Source::class, ['name' => 'TEST Source 1']);

        $I->amOnPage('/admin/source/' . $source->getId() . '/edit');

        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function adminCanDeleteASource(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $source = $I->grabEntityFromRepository(Source::class, ['name' => 'TEST Source 1']);
        $id = $source->getId();

        $I->amOnPage('/admin/source');
        $I->submitForm('form[action="/admin/source/' . $id . '/delete"]', []);

        $I->seeCurrentRouteIs('app.admin.source.index');
        $I->dontSeeInRepository(Source::class, ['name' => 'TEST Source 1']);
    }

    /**
     * With the default page size of 10 and twelve extra sources seeded on top of
     * the fixture's two, the first page must hold ten rows and a pagination block.
     * @param FunctionalTester $I
     * @return void
     */
    public function listingShowsTenItemsAndRendersPaginationByDefault(FunctionalTester $I): void
    {
        $this->seedExtraSources($I, 12);

        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/source');

        $I->seeResponseCodeIsSuccessful();
        $rows = $I->grabMultiple('//tbody/tr');
        $I->assertCount(10, $rows);
        $I->seeElement('#pagination');
        $I->seeElement('//a[@rel="next"]');
    }

    /**
     * Navigating to page=2 surfaces the rest of the dataset.
     * @param FunctionalTester $I
     * @return void
     */
    public function secondPageShowsTheRemainingItems(FunctionalTester $I): void
    {
        // Fixture provides 2 sources; seeding 12 more brings the total to 14 (10 + 4).
        $this->seedExtraSources($I, 12);

        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/source?page=2');

        $I->seeResponseCodeIsSuccessful();
        $rows = $I->grabMultiple('//tbody/tr');
        $I->assertCount(5, $rows);
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
        $this->seedExtraSources($I, 18);

        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/source?pageSize=20');

        $I->seeResponseCodeIsSuccessful();
        $rows = $I->grabMultiple('//tbody/tr');
        // Fixture's 2 sources + 18 inline sources = exactly 20 rows.
        $I->assertCount(20, $rows);
        // $I->dontSeeElement('#pagination');
    }

    /**
     * An out-of-whitelist pageSize value falls back to the first option (10 rows).
     * @param FunctionalTester $I
     * @return void
     */
    public function pageSizeFallsBackToTheDefaultWhenTheValueIsNotAllowed(FunctionalTester $I): void
    {
        $this->seedExtraSources($I, 12);

        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/source?pageSize=25');

        $I->seeResponseCodeIsSuccessful();
        $rows = $I->grabMultiple('//tbody/tr');
        $I->assertCount(10, $rows);
    }

    /**
     * The page-size selector renders every option from %items_per_page% and pre-selects
     * the current value (default when no query string is provided).
     * @param FunctionalTester $I
     * @return void
     */
    public function pageSizeSelectorExposesEveryWhitelistedOption(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/source');

        $I->seeResponseCodeIsSuccessful();
        $values = $I->grabMultiple('//select[@id="page-size-select"]/option', 'value');
        $I->assertSame(['10', '20', '50', '100'], $values);
        $I->seeElement('//select[@id="page-size-select"]/option[@value="10"][@selected]');
    }

    /**
     * Selecting a non-default page size keeps it pre-selected on the rendered listing.
     * @param FunctionalTester $I
     * @return void
     */
    public function pageSizeSelectorReflectsTheRequestedValue(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/source?pageSize=100');

        $I->seeResponseCodeIsSuccessful();
        $I->seeElement('//select[@id="page-size-select"]/option[@value="100"][@selected]');
        $I->dontSeeElement('//select[@id="page-size-select"]/option[@value="10"][@selected]');
    }

    private function seedExtraSources(FunctionalTester $I, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $I->haveInRepository(Source::class, [
                'name' => 'Pagination Source ' . $i,
                'url' => 'https://feedwatch.local/pagination/' . $i,
                'format' => FormatEnum::XML,
                'status' => StatusEnum::ACTIVE,
                'periodicity' => PeriodicityEnum::HOURLY,
            ]);
        }
    }
}
