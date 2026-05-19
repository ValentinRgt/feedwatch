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

    /**
     * Test that the command successfully deletes an existing user when the deletion is confirmed.
     * @param FunctionalTester $I
     * @return void
     */
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

    /**
     * Test that the command does not delete the user when the deletion is cancelled.
     * @param FunctionalTester $I
     * @return void
     */
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

    /**
     * Test that the command fails with an appropriate error message when attempting to delete a user that does not exist.
     * @param FunctionalTester $I
     * @return void
     */
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
