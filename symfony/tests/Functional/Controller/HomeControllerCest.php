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
use App\Tests\Support\FunctionalTester;

class HomeControllerCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->loadFixtures([
            $I->grabService(CategoryFixture::class),
        ]);
    }

    public function homePageIsPubliclyAccessible(FunctionalTester $I): void
    {
        $I->amOnPage('/');

        $I->seeResponseCodeIsSuccessful();
        $I->seeCurrentRouteIs('app.home');
    }

    public function homePageRendersTheIndexTemplate(FunctionalTester $I): void
    {
        $I->amOnPage('/');

        $I->seeInTitle('FeedWatch :: Home');
    }

    public function anonymousUserSeesTheLoginLink(FunctionalTester $I): void
    {
        $I->amOnPage('/');

        $I->dontSeeAuthentication();
        $I->seeElement('a[href="/login"]');
    }

    public function homePageListsTheAvailableCategories(FunctionalTester $I): void
    {
        $I->amOnPage('/');

        $category = $I->grabEntityFromRepository(Category::class, [
            'name' => 'TEST Category 0',
        ]);

        $I->see('TEST Category 0');
        $I->seeElement('a[href="/?category=' . $category->getId() . '"]');
    }

    public function homePageHighlightsTheAllFeedsTabByDefault(FunctionalTester $I): void
    {
        $I->amOnPage('/');

        $I->seeElement('a[href="/"][aria-current="page"]');
    }

    public function selectingACategoryMarksItAsCurrent(FunctionalTester $I): void
    {
        $category = $I->grabEntityFromRepository(Category::class, [
            'name' => 'TEST Category 3',
        ]);

        $I->amOnPage('/?category=' . $category->getId());

        $I->seeResponseCodeIsSuccessful();
        $I->seeElement(
            'a[href="/?category=' . $category->getId() . '"][aria-current="page"]'
        );
        $I->dontSeeElement('a[href="/"][aria-current="page"]');
    }

    public function unknownCategoryFallsBackToTheAllFeedsTab(FunctionalTester $I): void
    {
        $I->amOnPage('/?category=999999');

        $I->seeResponseCodeIsSuccessful();
        $I->seeElement('a[href="/"][aria-current="page"]');
    }

    public function searchQueryFiltersFeedsByArticleTitle(FunctionalTester $I): void
    {
        $source = $this->createSource($I, 'Generic Feed');
        $I->haveInRepository(Article::class, [
            'title' => 'Symfony 8 released',
            'link' => 'l-match',
            'source' => $source,
        ]);
        $I->haveInRepository(Article::class, [
            'title' => 'Other tech news',
            'link' => 'l-miss',
            'source' => $source,
        ]);

        $I->amOnPage('/?q=symfony');

        $I->seeResponseCodeIsSuccessful();
        $I->see('Symfony 8 released');
        $I->dontSee('Other tech news');
    }

    public function searchQueryEchoesIntoTheSearchInput(FunctionalTester $I): void
    {
        $I->amOnPage('/?q=symfony');

        $I->seeResponseCodeIsSuccessful();
        $I->seeElement('//input[@id="search-query"][@name="q"][@value="symfony"]');
    }

    public function searchQueryCombinesWithTheCategoryFilter(FunctionalTester $I): void
    {
        $category = $I->grabEntityFromRepository(Category::class, ['name' => 'TEST Category 0']);
        $matching = $this->createSource($I, 'In-Category Feed', $category);
        $other = $this->createSource($I, 'Other Feed');

        $I->haveInRepository(Article::class, [
            'title' => 'Symfony in category',
            'link' => 'l-cat-match',
            'source' => $matching,
        ]);
        $I->haveInRepository(Article::class, [
            'title' => 'Symfony outside category',
            'link' => 'l-cat-miss',
            'source' => $other,
        ]);

        $I->amOnPage('/?category=' . $category->getId() . '&q=symfony');

        $I->seeResponseCodeIsSuccessful();
        $I->see('Symfony in category');
        $I->dontSee('Symfony outside category');
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
}
