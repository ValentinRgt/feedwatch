<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Source;
use App\Enum\FormatEnum;
use App\Enum\PeriodicityEnum;
use App\Enum\StatusEnum;
use App\Fixture\Test\CategoryFixture;
use App\Fixture\Test\SourceFixture;
use App\Fixture\Test\UserFixture;
use App\Tests\Support\FunctionalTester;
use Codeception\Util\HttpCode;
use DateTimeImmutable;

class ArticleControllerCest
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
        $I->amOnPage('/admin/article');

        $I->seeCurrentRouteIs('app.login');
    }

    public function regularUserIsDeniedAccess(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::USER_EMAIL);

        $I->amOnPage('/admin/article');

        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function adminCanAccessTheArticleListing(FunctionalTester $I): void
    {
        $source = $this->grabFixtureSource($I);
        $I->haveInRepository(Article::class, [
            'title' => 'First article',
            'link' => 'https://feedwatch.local/articles/1',
            'source' => $source,
        ]);

        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/article');

        $I->seeResponseCodeIsSuccessful();
        $I->seeCurrentRouteIs('app.admin.article.index');
        $I->see('Article Management');
        $I->see('First article');
    }

    public function listingShowsTheEmptyStateWhenNoArticleExists(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/article');

        $I->seeResponseCodeIsSuccessful();
        $I->see('No articles have been fetched yet.');
    }

    public function listingOrdersArticlesByPublishedThenCreatedDateDesc(FunctionalTester $I): void
    {
        $source = $this->grabFixtureSource($I);
        $I->haveInRepository(Article::class, [
            'title' => 'Older Article',
            'link' => 'l-old',
            'source' => $source,
            'publishedAt' => new DateTimeImmutable('2026-04-10 09:00:00'),
        ]);
        $I->haveInRepository(Article::class, [
            'title' => 'Newer Article',
            'link' => 'l-new',
            'source' => $source,
            'publishedAt' => new DateTimeImmutable('2026-05-20 09:00:00'),
        ]);

        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/article');

        $I->seeResponseCodeIsSuccessful();
        $titles = $I->grabMultiple('//tbody/tr//th[1]//span');
        $I->assertSame(['Newer Article', 'Older Article'], $titles);
    }

    public function searchQueryFiltersArticlesByTitleSourceOrCategoryName(FunctionalTester $I): void
    {
        $tech = $I->grabEntityFromRepository(Category::class, ['name' => 'TEST Category 0']);
        $techSource = $this->createSource($I, 'TechCrunch Feed', $tech);
        $sportSource = $this->grabFixtureSource($I);

        $I->haveInRepository(Article::class, ['title' => 'Symfony release', 'link' => 'l-1', 'source' => $sportSource]);
        $I->haveInRepository(Article::class, ['title' => 'Football news', 'link' => 'l-2', 'source' => $techSource]);
        $I->haveInRepository(Article::class, ['title' => 'Misc story', 'link' => 'l-3', 'source' => $sportSource]);

        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/article?q=symfony');

        $I->seeResponseCodeIsSuccessful();
        $rows = $I->grabMultiple('//tbody/tr');
        $I->assertCount(1, $rows);
        $I->see('Symfony release');
        $I->dontSee('Football news');
        $I->dontSee('Misc story');
    }

    public function searchQueryEchoesIntoTheSearchInput(FunctionalTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/article?q=symfony');

        $I->seeResponseCodeIsSuccessful();
        $I->seeElement('//input[@id="search-query"][@name="q"][@value="symfony"]');
    }

    public function pageSizeQueryParameterControlsTheNumberOfRows(FunctionalTester $I): void
    {
        $source = $this->grabFixtureSource($I);
        $this->seedExtraArticles($I, 49, $source);

        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/article?pageSize=20');

        $I->seeResponseCodeIsSuccessful();
        $rows = $I->grabMultiple('//tbody/tr');
        $I->assertCount(20, $rows);
        $I->seeElement('#pagination');
    }

    public function adminCanDeleteAnArticle(FunctionalTester $I): void
    {
        $source = $this->grabFixtureSource($I);
        $articleId = $I->haveInRepository(Article::class, [
            'title' => 'To be deleted',
            'link' => 'l-delete',
            'source' => $source,
        ]);

        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/article');
        $I->submitForm('form[action="/admin/article/' . $articleId . '/delete"]', []);

        $I->seeCurrentRouteIs('app.admin.article.index');
        $I->see('Article deleted successfully.');
        $I->dontSeeInRepository(Article::class, ['id' => $articleId]);
    }

    private function grabFixtureSource(FunctionalTester $I): Source
    {
        return $I->grabEntityFromRepository(Source::class, ['name' => 'TEST Source 0']);
    }

    private function createSource(FunctionalTester $I, string $name, ?Category $category = null): Source
    {
        $id = $I->haveInRepository(Source::class, [
            'name' => $name,
            'url' => 'https://feedwatch.local/' . $name,
            'format' => FormatEnum::XML,
            'status' => StatusEnum::ACTIVE,
            'periodicity' => PeriodicityEnum::HOURLY,
            'category' => $category,
        ]);

        return $I->grabEntityFromRepository(Source::class, ['id' => $id]);
    }

    private function seedExtraArticles(FunctionalTester $I, int $count, Source $source): void
    {
        for ($i = 0; $i < $count; $i++) {
            $I->haveInRepository(Article::class, [
                'title' => 'Pagination Article ' . $i,
                'link' => 'https://feedwatch.local/articles/pagination/' . $i,
                'source' => $source,
            ]);
        }
    }
}
