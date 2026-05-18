<?php

declare(strict_types=1);

namespace App\Fixture\Data;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class CategoryFixture extends Fixture implements FixtureGroupInterface
{
    public const string CATEGORY_REFERENCE = 'category_';

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 10; $i++) {
            $category = new Category();
            $category->setName('Category ' . $i);
            $this->addReference(self::CATEGORY_REFERENCE . $i, $category);
            $manager->persist($category);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['dev'];
    }
}
