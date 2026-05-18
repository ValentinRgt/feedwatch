<?php

declare(strict_types=1);

namespace App\Fixture\Test;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class CategoryFixture extends Fixture implements FixtureGroupInterface
{
    public const string CATEGORY_REFERENCE = 'test_category_';

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 20; $i++) {
            $category = new Category();
            $category->setName('TEST Category ' . $i);
            $this->addReference(self::CATEGORY_REFERENCE . $i, $category);
            $manager->persist($category);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['test'];
    }
}
