<?php

declare(strict_types=1);

namespace App\Tests\Functional\Command;

use App\Entity\User;
use App\Fixture\Test\UserFixture;
use App\Tests\Support\FunctionalTester;

class UserDeleteCommandCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->loadFixtures([
            $I->grabService(UserFixture::class),
        ]);
    }

    public function itDeletesAUserWhenConfirmed(FunctionalTester $I): void
    {
        $output = $I->runSymfonyConsoleCommand(
            'app:user:delete',
            ['email' => UserFixture::USER_EMAIL],
            ['yes'],
            0
        );

        $I->assertStringContainsString('deleted successfully', $output);
        $I->dontSeeInRepository(User::class, ['email' => UserFixture::USER_EMAIL]);
    }

    public function itKeepsTheUserWhenDeletionIsCancelled(FunctionalTester $I): void
    {
        $output = $I->runSymfonyConsoleCommand(
            'app:user:delete',
            ['email' => UserFixture::USER_EMAIL],
            ['no'],
            0
        );

        $I->assertStringContainsString('cancelled', $output);
        $I->seeInRepository(User::class, ['email' => UserFixture::USER_EMAIL]);
    }

    public function itFailsForAnUnknownEmail(FunctionalTester $I): void
    {
        $output = $I->runSymfonyConsoleCommand(
            'app:user:delete',
            ['email' => 'ghost@feedwatch.local'],
            [],
            1
        );

        $I->assertStringContainsString('No user found', $output);
    }
}
