<?php

declare(strict_types=1);

namespace App\Tests\Functional\Command;

use App\Entity\User;
use App\Fixture\Test\UserFixture;
use App\Tests\Support\FunctionalTester;

class UserUpdateCommandCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->loadFixtures([
            $I->grabService(UserFixture::class),
        ]);
    }

    /**
     * Test that the command successfully updates the username of an existing user without changing the password when an empty password is provided.
     * @param FunctionalTester $I
     * @return void
     */
    public function itUpdatesTheUsernameWithoutChangingThePassword(FunctionalTester $I): void
    {
        $before = $I->grabEntityFromRepository(User::class, ['email' => UserFixture::ADMIN_EMAIL]);
        $originalPassword = $before->getPassword();

        // Username "RenamedAdmin", then an empty password line to keep the current one.
        $output = $I->runSymfonyConsoleCommand(
            'app:user:update',
            ['email' => UserFixture::ADMIN_EMAIL],
            ['RenamedAdmin', ''],
            0
        );

        $I->assertStringContainsString('updated successfully', $output);

        $user = $I->grabEntityFromRepository(User::class, ['email' => UserFixture::ADMIN_EMAIL]);
        $I->assertSame('RenamedAdmin', $user->getUsername());
        $I->assertSame($originalPassword, $user->getPassword());
    }

    /**
     * Test that the command fails with an appropriate error message when attempting to update a user that does not exist.
     * @param FunctionalTester $I
     * @return void
     */
    public function itFailsForAnUnknownEmail(FunctionalTester $I): void
    {
        $output = $I->runSymfonyConsoleCommand(
            'app:user:update',
            ['email' => 'unknown@feedwatch.local'],
            [],
            1
        );

        $I->assertStringContainsString('No user found', $output);
    }
}
