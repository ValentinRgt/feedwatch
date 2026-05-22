<?php

declare(strict_types=1);

namespace App\Fixture\Test;

use App\Entity\Category;
use App\Entity\Source;
use App\Enum\FormatEnum;
use App\Enum\PeriodicityEnum;
use App\Enum\StatusEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SourceFixture extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public const string SOURCE_REFERENCE = 'test_source_';

    public function load(ObjectManager $manager): void
    {
        $first = new Source();
        $first->setName('TEST Source 0');
        $first->setUrl('https://feedwatch.local/feed/0');
        $first->setFormat(FormatEnum::XML);
        $first->setStatus(StatusEnum::ACTIVE);
        $first->setPeriodicity(PeriodicityEnum::HOURLY);
        $first->setChecksum('seeded-checksum');
        $first->setCategory($this->getReference(CategoryFixture::CATEGORY_REFERENCE . '0', Category::class));
        $this->addReference(self::SOURCE_REFERENCE . '0', $first);
        $manager->persist($first);

        $second = new Source();
        $second->setName('TEST Source 1');
        $second->setUrl('https://feedwatch.local/feed/1');
        $second->setFormat(FormatEnum::HTML);
        $second->setStatus(StatusEnum::INACTIVE);
        $second->setPeriodicity(PeriodicityEnum::DAILY);
        $this->addReference(self::SOURCE_REFERENCE . '1', $second);
        $manager->persist($second);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [CategoryFixture::class];
    }

    public static function getGroups(): array
    {
        return ['test'];
    }
}
