<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Interface\UserInterface;
use App\Repository\UserRepository;
use App\Service\UserService;
use App\Tests\Support\UnitTester;
use Codeception\Stub;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Unit tests for the *real* UserService logic.
 *
 * UserRepository is replaced by a tiny in-memory subclass: its
 * findOneByEmail() is a magic (@method) call that cannot be stubbed through
 * Codeception\Stub, and the subclass also lets us capture save()/remove()
 * calls. The password hasher is a simple stub that prefixes the plain text.
 */
class UserServiceCest
{
    /**
     * In-memory UserRepository double recording persisted/removed users.
     */
    private function repository(?User $found = null): UserRepository
    {
        return new class ($found) extends UserRepository {
            /** @var User[] */
            public array $saved = [];
            /** @var User[] */
            public array $removed = [];

            public function __construct(private readonly ?User $found = null)
            {
            }

            public function findOneByEmail(string $email): ?User
            {
                return $this->found;
            }

            public function save(User $user, bool $flush = false): void
            {
                $this->saved[] = $user;
            }

            public function remove(User $user, bool $flush = false): void
            {
                $this->removed[] = $user;
            }
        };
    }

    private function hasher(): UserPasswordHasherInterface
    {
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = Stub::makeEmpty(UserPasswordHasherInterface::class, [
            'hashPassword' => fn (object $user, string $plain): string => 'hashed:' . $plain,
        ]);

        return $hasher;
    }

    public function serviceImplementsTheUserInterfaceContract(UnitTester $I): void
    {
        $service = new UserService($this->repository(), $this->hasher());

        $I->assertInstanceOf(UserInterface::class, $service);
    }

    public function alreadyExistIsTrueWhenTheRepositoryFindsAUser(UnitTester $I): void
    {
        $service = new UserService($this->repository(new User()), $this->hasher());

        $I->assertTrue($service->alreadyExist('known@feedwatch.local'));
    }

    public function alreadyExistIsFalseWhenTheRepositoryFindsNothing(UnitTester $I): void
    {
        $service = new UserService($this->repository(null), $this->hasher());

        $I->assertFalse($service->alreadyExist('missing@feedwatch.local'));
    }

    public function findByEmailReturnsTheUserFromTheRepository(UnitTester $I): void
    {
        $expected = (new User())->setEmail('john@feedwatch.local');
        $service = new UserService($this->repository($expected), $this->hasher());

        $I->assertSame($expected, $service->findByEmail('john@feedwatch.local'));
    }

    public function createAdminHashesPasswordAndAssignsTheAdminRole(UnitTester $I): void
    {
        $repository = $this->repository();
        $service = new UserService($repository, $this->hasher());

        $service->createAdmin('admin@feedwatch.local', 'Admin', 'plain-password');

        $I->assertCount(1, $repository->saved);
        $created = $repository->saved[0];
        $I->assertSame('admin@feedwatch.local', $created->getEmail());
        $I->assertSame('Admin', $created->getUsername());
        $I->assertContains('ROLE_ADMIN', $created->getRoles());
        $I->assertSame('hashed:plain-password', $created->getPassword());
    }

    public function updateAdminChangesUsernameAndRehashesPasswordWhenProvided(UnitTester $I): void
    {
        $repository = $this->repository();
        $service = new UserService($repository, $this->hasher());
        $user = (new User())
            ->setUsername('Old')
            ->setPassword('previous-hash');

        $service->updateAdmin($user, 'NewName', 'new-password');

        $I->assertSame('NewName', $user->getUsername());
        $I->assertSame('hashed:new-password', $user->getPassword());
        $I->assertSame([$user], $repository->saved);
    }

    public function updateAdminKeepsTheCurrentPasswordWhenNoneIsProvided(UnitTester $I): void
    {
        $repository = $this->repository();
        $service = new UserService($repository, $this->hasher());
        $user = (new User())
            ->setUsername('Old')
            ->setPassword('previous-hash');

        $service->updateAdmin($user, 'NewName', null);

        $I->assertSame('NewName', $user->getUsername());
        $I->assertSame('previous-hash', $user->getPassword());
        $I->assertSame([$user], $repository->saved);
    }

    public function deleteAdminRemovesTheUserFromTheRepository(UnitTester $I): void
    {
        $repository = $this->repository();
        $service = new UserService($repository, $this->hasher());
        $user = (new User())->setEmail('admin@feedwatch.local');

        $service->deleteAdmin($user);

        $I->assertSame([$user], $repository->removed);
    }
}
