<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Source;
use App\Entity\SourceError;
use App\Fixture\Test\CategoryFixture;
use App\Fixture\Test\SourceFixture;
use App\Fixture\Test\UserFixture;
use App\Tests\Support\FunctionalTester;
use Codeception\Util\HttpCode;
use DateTimeImmutable;

class MonitoringControllerCest
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
        $I->amOnPage('/admin/monitoring');

        $I->seeCurrentRouteIs('app.login');
    }

    public function regularUserIsDeniedAccess(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::USER_EMAIL);

        $I->amOnPage('/admin/monitoring');

        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function adminCanAccessTheMonitoringPageWhenNoErrorIsRecorded(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/monitoring');

        $I->seeResponseCodeIsSuccessful();
        $I->seeCurrentRouteIs('app.admin.monitoring.index');
        $I->see('Source fetch errors');
        $I->see('No errors recorded. All sources are running smoothly.');
    }

    public function listingShowsTheSourceErrorsMostRecentFirst(FunctionalTester $I): void
    {
        $source = $I->grabEntityFromRepository(Source::class, ['name' => 'TEST Source 0']);

        $olderId = $I->haveInRepository(SourceError::class, [
            'source' => $source,
            'exceptionClass' => 'RuntimeException',
            'message' => 'older failure',
        ]);
        $newerId = $I->haveInRepository(SourceError::class, [
            'source' => $source,
            'exceptionClass' => 'LogicException',
            'message' => 'newer failure',
        ]);

        // The DateTimeImmutableListener overrides createdAt on prePersist, so the dates have
        // to be back-dated after the fact for the DESC ordering to be observable.
        $older = $I->grabEntityFromRepository(SourceError::class, ['id' => $olderId]);
        $older->setCreatedAt(new DateTimeImmutable('-2 hours'));
        $newer = $I->grabEntityFromRepository(SourceError::class, ['id' => $newerId]);
        $newer->setCreatedAt(new DateTimeImmutable('-5 minutes'));
        $I->flushToDatabase();

        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/monitoring');

        $I->seeResponseCodeIsSuccessful();
        $I->see('TEST Source 0');
        $I->dontSee('No errors recorded.');

        // The bare class name is rendered in the "Exception" column.
        $I->see('RuntimeException');
        $I->see('LogicException');

        // Ordering: newest row first. The "Message" cell is the second <td> after the leading <th>.
        $messages = $I->grabMultiple('//tbody//tr/td[2]/span');
        $I->assertSame(['newer failure', 'older failure'], $messages);
    }

    public function deletingAnErrorWithAValidCsrfTokenRemovesIt(FunctionalTester $I): void
    {
        $source = $I->grabEntityFromRepository(Source::class, ['name' => 'TEST Source 0']);
        $errorId = $I->haveInRepository(SourceError::class, [
            'source' => $source,
            'exceptionClass' => 'RuntimeException',
            'message' => 'transient failure',
        ]);

        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/monitoring');
        $I->submitForm('form[action="/admin/monitoring/' . $errorId . '/delete"]', []);

        $I->seeCurrentRouteIs('app.admin.monitoring.index');
        $I->dontSeeInRepository(SourceError::class, ['id' => $errorId]);
    }

    public function deletingAnErrorWithAnInvalidCsrfTokenIsANoop(FunctionalTester $I): void
    {
        $source = $I->grabEntityFromRepository(Source::class, ['name' => 'TEST Source 0']);
        $errorId = $I->haveInRepository(SourceError::class, [
            'source' => $source,
            'exceptionClass' => 'RuntimeException',
            'message' => 'transient failure',
        ]);

        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/monitoring');
        // Submitting the row's delete form with a tampered CSRF token must not delete the entry.
        $I->submitForm(
            'form[action="/admin/monitoring/' . $errorId . '/delete"]',
            ['_token' => 'tampered'],
        );

        $I->seeCurrentRouteIs('app.admin.monitoring.index');
        $I->seeInRepository(SourceError::class, ['id' => $errorId]);
    }

    /**
     * With twelve seeded errors and the default page size of 10, the first page
     * must show exactly ten rows and render the pagination block.
     * @param FunctionalTester $I
     * @return void
     */
    public function listingShowsTenItemsAndRendersPaginationByDefault(FunctionalTester $I): void
    {
        $this->seedSourceErrors($I, 12);

        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/monitoring');

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
        $this->seedSourceErrors($I, 12);

        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/monitoring?page=2');

        $I->seeResponseCodeIsSuccessful();
        $rows = $I->grabMultiple('//tbody/tr');
        $I->assertCount(2, $rows);
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
        $this->seedSourceErrors($I, 20);

        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/monitoring?pageSize=20');

        $I->seeResponseCodeIsSuccessful();
        $rows = $I->grabMultiple('//tbody/tr');
        $I->assertCount(20, $rows);
        $I->dontSeeElement('#pagination');
    }

    /**
     * An out-of-whitelist pageSize value falls back to the first option (10 rows).
     * @param FunctionalTester $I
     * @return void
     */
    public function pageSizeFallsBackToTheDefaultWhenTheValueIsNotAllowed(FunctionalTester $I): void
    {
        $this->seedSourceErrors($I, 12);

        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/monitoring?pageSize=25');

        $I->seeResponseCodeIsSuccessful();
        $rows = $I->grabMultiple('//tbody/tr');
        $I->assertCount(10, $rows);
    }

    /**
     * The page-size selector renders every option from %items_per_page% and pre-selects
     * the current value.
     * @param FunctionalTester $I
     * @return void
     */
    public function pageSizeSelectorExposesEveryWhitelistedOption(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/monitoring');

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

        $I->amOnPage('/admin/monitoring?pageSize=50');

        $I->seeResponseCodeIsSuccessful();
        $I->seeElement('//select[@id="page-size-select"]/option[@value="50"][@selected]');
        $I->dontSeeElement('//select[@id="page-size-select"]/option[@value="10"][@selected]');
    }

    private function seedSourceErrors(FunctionalTester $I, int $count): void
    {
        $source = $I->grabEntityFromRepository(Source::class, ['name' => 'TEST Source 0']);

        for ($i = 0; $i < $count; $i++) {
            $I->haveInRepository(SourceError::class, [
                'source' => $source,
                'exceptionClass' => 'RuntimeException',
                'message' => 'failure ' . $i,
            ]);
        }
    }
}
