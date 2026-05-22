<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Source;
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
}
