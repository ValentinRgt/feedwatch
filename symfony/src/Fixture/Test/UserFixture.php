<?php

declare(strict_types=1);

namespace App\Fixture\Test;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixture extends Fixture implements FixtureGroupInterface
{
    public const string ADMIN_EMAIL = "admin@feedwatch.local";
    public const string USER_EMAIL = "user@feedwatch.local";
    public const string PASSWORD = "azertyuiop";

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail(self::ADMIN_EMAIL);
        $admin->setUsername('Admin');
        $admin->setRoles(['ROLE_ADMIN']);
        $hashedPassword = $this->passwordHasher->hashPassword($admin, self::PASSWORD);
        $admin->setPassword($hashedPassword);

        $manager->persist($admin);

        $user = new User();
        $user->setEmail(self::USER_EMAIL);
        $user->setUsername('User');
        $hashedPassword = $this->passwordHasher->hashPassword($user, self::PASSWORD);
        $user->setPassword($hashedPassword);

        $manager->persist($user);

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['test'];
    }
}
