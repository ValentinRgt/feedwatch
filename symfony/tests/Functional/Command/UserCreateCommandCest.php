<?php

declare(strict_types=1);

namespace App\Tests\Functional\Command;

use App\Entity\User;
use App\Fixture\Test\UserFixture;
use App\Tests\Support\FunctionalTester;

class UserCreateCommandCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->loadFixtures([
            $I->grabService(UserFixture::class),
        ]);
    }

    /**
     * Test that the command successfully creates a new admin user when provided with valid input.
     * @param FunctionalTester $I
     * @return void
     */
    public function itCreatesANewAdminUser(FunctionalTester $I): void
    {
        $output = $I->runSymfonyConsoleCommand(
            'app:user:create',
            [],
            ['new.admin@feedwatch.local', 'NewAdmin', 'super-secret', 'super-secret'],
            0
        );

        $I->assertStringContainsString('created successfully', $output);

        $user = $I->grabEntityFromRepository(User::class, ['email' => 'new.admin@feedwatch.local']);
        $I->assertSame('NewAdmin', $user->getUsername());
        $I->assertContains('ROLE_ADMIN', $user->getRoles());
    }

    /**
    * Test that the command fails when the provided email is already associated with an existing user.
    * @param FunctionalTester $I
    * @return void
    */
    public function itFailsWhenPasswordsDoNotMatch(FunctionalTester $I): void
    {
        $output = $I->runSymfonyConsoleCommand(
            'app:user:create',
            [],
            ['mismatch@feedwatch.local', 'Mismatch', 'password-one', 'password-two'],
            1
        );

        $I->assertStringContainsString('Passwords do not match', $output);
    }
}
