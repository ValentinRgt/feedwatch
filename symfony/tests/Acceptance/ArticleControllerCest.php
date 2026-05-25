<?php

declare(strict_types=1);

namespace App\Tests\Acceptance;

use App\Entity\Article;
use App\Entity\Source;
use App\Fixture\Test\CategoryFixture;
use App\Fixture\Test\SourceFixture;
use App\Fixture\Test\UserFixture;
use App\Tests\Support\AcceptanceTester;
use Codeception\Util\HttpCode;

class ArticleControllerCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loadFixtures([
            $I->grabService(UserFixture::class),
            $I->grabService(CategoryFixture::class),
            $I->grabService(SourceFixture::class),
        ]);
    }

    public function anonymousUserIsRedirectedToLogin(AcceptanceTester $I): void
    {
        $I->amOnPage('/admin/article');

        $I->seeInCurrentUrl('/login');
        $I->see('Log in');
    }

    public function regularUserCannotAccessTheArticleAdmin(AcceptanceTester $I): void
    {
        $I->loginAsAUser(UserFixture::USER_EMAIL);

        $I->amOnPage('/admin/article');

        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function adminSeesTheEmptyStateWhenNoArticleExists(AcceptanceTester $I): void
    {
        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/article');

        $I->seeResponseCodeIsSuccessful();
        $I->see('Article Management');
        $I->see('No articles have been fetched yet.');
    }

    /**
     * Full round-trip: an admin seeds an article (via the repository, because the
     * pipeline normally creates them from feed ingestion), confirms it appears in
     * the listing, filters it with the search bar, and removes it via the row form.
     * @param AcceptanceTester $I
     * @return void
     */
    public function adminCanSearchAndDeleteAnArticle(AcceptanceTester $I): void
    {
        $source = $I->grabEntityFromRepository(Source::class, ['name' => 'TEST Source 0']);
        $articleId = $I->haveInRepository(Article::class, [
            'title' => 'Acceptance E2E Article',
            'link' => 'https://feedwatch.local/acceptance-e2e-article',
            'source' => $source,
        ]);

        $I->loginAsAUser(UserFixture::ADMIN_EMAIL);

        $I->amOnPage('/admin/article');
        $I->see('Acceptance E2E Article');

        // The search bar narrows the listing down to the seeded row.
        $I->amOnPage('/admin/article?q=acceptance');
        $I->see('Acceptance E2E Article');
        $I->seeElement('//input[@id="search-query"][@value="acceptance"]');

        // Delete via the row's form action (CSRF token comes from the rendered form).
        $deleteAction = $I->grabAttributeFrom(
            '//tr[.//span[normalize-space()="Acceptance E2E Article"]]//form[contains(@action,"/delete")]',
            'action'
        );
        $I->submitForm('form[action="' . $deleteAction . '"]', []);

        $I->seeInCurrentUrl('/admin/article');
        $I->dontSee('Acceptance E2E Article');
        $I->dontSeeInRepository(Article::class, ['id' => $articleId]);
    }
}
