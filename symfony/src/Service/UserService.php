<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Interface\UserInterface;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

readonly class UserService implements UserInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function alreadyExist(string $email): bool
    {
        return null !== $this->userRepository->findOneByEmail($email);
    }

    /**
     * @inheritdoc
     */
    public function createAdmin(string $email, string $username, string $password): void
    {
        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setRoles(['ROLE_ADMIN']);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user, true);
    }
}
