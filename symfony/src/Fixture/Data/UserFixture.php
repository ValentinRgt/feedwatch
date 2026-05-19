<?php

declare(strict_types=1);

namespace App\Fixture\Data;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixture extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('admin@feedwatch.local');
        $user->setUsername('Admin');
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'azertyuiop');
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_ADMIN']);

        $manager->persist($user);

        $regular = new User();
        $regular->setEmail('user@feedwatch.local');
        $regular->setUsername('User');
        $hashedPassword = $this->passwordHasher->hashPassword($regular, 'azertyuiop');
        $regular->setPassword($hashedPassword);

        $manager->persist($regular);

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['dev'];
    }
}
