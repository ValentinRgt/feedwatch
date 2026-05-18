<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Interface\UserInterface;
use App\Repository\UserRepository;
use App\Service\UserService;
use App\Tests\Support\UnitTester;
use Codeception\Stub;

/**
 * Per request, both UserService and UserRepository are mocked here.
 *
 * Note: because UserService itself is doubled, these tests exercise the
 * mocked contract (return values / callability of UserInterface and
 * UserRepository), not the real internal logic of UserService.
 */
class UserServiceCest
{
    private function repository(): UserRepository
    {
        /** @var UserRepository $repository */
        $repository = Stub::makeEmpty(UserRepository::class);

        return $repository;
    }

    public function alreadyExistReflectsTheMockedReturnValue(UnitTester $I): void
    {
        /** @var UserService $service */
        $service = Stub::makeEmpty(UserService::class, [
            'alreadyExist' => fn (string $email): bool => 'known@feedwatch.local' === $email,
        ]);

        $I->assertTrue($service->alreadyExist('known@feedwatch.local'));
        $I->assertFalse($service->alreadyExist('other@feedwatch.local'));
    }

    public function findByEmailReturnsTheMockedUser(UnitTester $I): void
    {
        $expected = (new User())->setEmail('john@feedwatch.local');
        /** @var UserService $service */
        $service = Stub::makeEmpty(UserService::class, [
            'findByEmail' => $expected,
        ]);

        $I->assertSame($expected, $service->findByEmail('john@feedwatch.local'));
    }

    public function findByEmailReturnsNullWhenMocked(UnitTester $I): void
    {
        /** @var UserService $service */
        $service = Stub::makeEmpty(UserService::class, [
            'findByEmail' => null,
        ]);

        $I->assertNull($service->findByEmail('missing@feedwatch.local'));
    }

    public function writeOperationsAreCallableOnTheMock(UnitTester $I): void
    {
        $calls = [];
        /** @var UserService $service */
        $service = Stub::makeEmpty(UserService::class, [
            'createAdmin' => function () use (&$calls): void {
                $calls[] = 'create';
            },
            'updateAdmin' => function () use (&$calls): void {
                $calls[] = 'update';
            },
            'deleteAdmin' => function () use (&$calls): void {
                $calls[] = 'delete';
            },
        ]);

        $user = (new User())->setEmail('admin@feedwatch.local');
        $service->createAdmin('admin@feedwatch.local', 'Admin', 'plain-password');
        $service->updateAdmin($user, 'Admin', null);
        $service->deleteAdmin($user);

        $I->assertSame(['create', 'update', 'delete'], $calls);
    }

    public function serviceMockHonorsTheUserInterfaceContract(UnitTester $I): void
    {
        /** @var UserService $service */
        $service = Stub::makeEmpty(UserService::class);

        $I->assertInstanceOf(UserInterface::class, $service);
    }

    public function repositoryMockIsAUserRepositoryInstance(UnitTester $I): void
    {
        $repository = $this->repository();

        $I->assertInstanceOf(UserRepository::class, $repository);
    }

    public function repositoryMockReturnsConfiguredValues(UnitTester $I): void
    {
        $user = (new User())->setEmail('john@feedwatch.local');
        /** @var UserRepository $repository */
        $repository = Stub::makeEmpty(UserRepository::class, [
            // "findOneByEmail" is magic (__call -> findOneBy), so stub findOneBy.
            'findOneBy' => $user,
        ]);

        $I->assertSame($user, $repository->findOneBy(['email' => 'john@feedwatch.local']));
    }
}
