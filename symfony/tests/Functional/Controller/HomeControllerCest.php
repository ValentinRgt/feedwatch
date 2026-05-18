<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Category;
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
}
