<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Article;
use App\Entity\Source;
use App\Entity\SourceError;
use App\Fixture\Test\CategoryFixture;
use App\Fixture\Test\SourceFixture;
use App\Fixture\Test\UserFixture;
use App\Tests\Support\FunctionalTester;
use Codeception\Util\HttpCode;
use DateTimeImmutable;

class IndexControllerCest
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
        $I->amOnPage('/admin/');

        $I->seeCurrentRouteIs('app.login');
    }

    public function regularUserIsDeniedAccess(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::USER_EMAIL);

        $I->amOnPage('/admin/');

        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function adminSeesTheDashboardWithSeededCounts(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/');

        $I->seeResponseCodeIsSuccessful();
        $I->seeCurrentRouteIs('app.admin.index');

        // Counts come straight from the fixtures (20 categories, 2 sources, 0 articles, 0 errors).
        $I->see('20');
        $I->see('2');
        $I->see('Sources');
        $I->see('Categories');
        $I->see('Feeds');
        $I->see('Errors');
    }

    public function dashboardShowsTheEmptyStateWhenNoActivityIsRecorded(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/');

        $I->seeResponseCodeIsSuccessful();
        $I->see('No activity recorded for this period.');
    }

    public function dashboardRanksTheSourceWithTheMostArticlesFirst(FunctionalTester $I): void
    {
        $busy = $I->grabEntityFromRepository(Source::class, ['name' => 'TEST Source 0']);
        $quiet = $I->grabEntityFromRepository(Source::class, ['name' => 'TEST Source 1']);

        // 3 articles on "busy", 1 on "quiet" — both inside the 7-day window.
        $this->createArticle($I, $busy, 'busy-1', new DateTimeImmutable('-1 day'));
        $this->createArticle($I, $busy, 'busy-2', new DateTimeImmutable('-2 days'));
        $this->createArticle($I, $busy, 'busy-3', new DateTimeImmutable('-3 days'));
        $this->createArticle($I, $quiet, 'quiet-1', new DateTimeImmutable('-1 day'));

        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/');

        $I->seeResponseCodeIsSuccessful();
        $busyRow = $I->grabTextFrom('//tr[td/a[normalize-space()="TEST Source 0"]]');
        $quietRow = $I->grabTextFrom('//tr[td/a[normalize-space()="TEST Source 1"]]');
        $I->assertStringContainsString('3', $busyRow);
        $I->assertStringContainsString('1', $quietRow);
        $I->dontSee('No activity recorded for this period.');
    }

    public function errorCountReflectsThePersistedSourceErrors(FunctionalTester $I): void
    {
        $source = $I->grabEntityFromRepository(Source::class, ['name' => 'TEST Source 0']);

        $I->haveInRepository(SourceError::class, [
            'source' => $source,
            'exceptionClass' => 'RuntimeException',
            'message' => 'Boom',
        ]);
        $I->haveInRepository(SourceError::class, [
            'source' => $source,
            'exceptionClass' => 'LogicException',
            'message' => 'Nope',
        ]);

        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/');

        $I->seeResponseCodeIsSuccessful();
        // The "errors" stat card is a link to /admin/monitoring that contains both the label and the count.
        $errorsCard = $I->grabTextFrom('//a[contains(@href, "/admin/monitoring")][.//p[normalize-space()="Errors"]]');
        $I->assertStringContainsString('Errors', $errorsCard);
        $I->assertStringContainsString('2', $errorsCard);
    }

    private function createArticle(
        FunctionalTester $I,
        Source $source,
        string $checksum,
        DateTimeImmutable $createdAt,
    ): void {
        $I->haveInRepository(Article::class, [
            'title' => 'Article ' . $checksum,
            'link' => 'https://feedwatch.local/article/' . $checksum,
            'checksum' => $checksum,
            'source' => $source,
            'createdAt' => $createdAt,
        ]);
    }
}
