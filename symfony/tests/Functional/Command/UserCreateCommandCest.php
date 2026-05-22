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

    /**
     * The email validator rejects a malformed address, then accepts a valid one
     * provided on the next attempt.
     * @param FunctionalTester $I
     * @return void
     */
    public function itRecoversFromAMalformedEmail(FunctionalTester $I): void
    {
        $output = $I->runSymfonyConsoleCommand(
            'app:user:create',
            [],
            ['not-an-email', 'valid.admin@feedwatch.local', 'ValidAdmin', 'super-secret', 'super-secret'],
            0
        );

        $I->assertStringContainsString('created successfully', $output);
        $I->seeInRepository(User::class, ['email' => 'valid.admin@feedwatch.local']);
    }

    /**
     * The email validator rejects an address that already belongs to a user,
     * then accepts a fresh one.
     * @param FunctionalTester $I
     * @return void
     */
    public function itRejectsAnAlreadyRegisteredEmailThenSucceeds(FunctionalTester $I): void
    {
        $output = $I->runSymfonyConsoleCommand(
            'app:user:create',
            [],
            [UserFixture::ADMIN_EMAIL, 'fresh.admin@feedwatch.local', 'FreshAdmin', 'super-secret', 'super-secret'],
            0
        );

        $I->assertStringContainsString('created successfully', $output);
        $I->seeInRepository(User::class, ['email' => 'fresh.admin@feedwatch.local']);
    }

    /**
     * The username and password validators reject too-short values, then accept
     * the corrected ones provided on the following attempts.
     * @param FunctionalTester $I
     * @return void
     */
    public function itRecoversFromTooShortUsernameAndPassword(FunctionalTester $I): void
    {
        $output = $I->runSymfonyConsoleCommand(
            'app:user:create',
            [],
            ['short.fields@feedwatch.local', 'ab', 'GoodName', 'short', 'long-enough', 'long-enough'],
            0
        );

        $I->assertStringContainsString('created successfully', $output);
        $user = $I->grabEntityFromRepository(User::class, ['email' => 'short.fields@feedwatch.local']);
        $I->assertSame('GoodName', $user->getUsername());
    }
}
