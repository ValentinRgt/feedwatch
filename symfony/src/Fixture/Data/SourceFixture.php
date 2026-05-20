<?php

declare(strict_types=1);

namespace App\Fixture\Data;

use App\Entity\Category;
use App\Entity\Source;
use App\Enum\FormatEnum;
use App\Enum\StatusEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SourceFixture extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public const string SOURCE_REFERENCE = 'source_';

    public function load(ObjectManager $manager): void
    {
        $source = new Source();
        $source->setName('dev.to - Web Development');
        $source->setUrl('https://dev.to/feed/tag/webdev');
        $source->setFormat(FormatEnum::XML);
        $source->setCategory($this->getReference(CategoryFixture::CATEGORY_REFERENCE . '0', Category::class));
        $source->setStatus(StatusEnum::ACTIVE);
        $this->addReference(self::SOURCE_REFERENCE . '0', $source);
        $manager->persist($source);

        $source = new Source();
        $source->setName('Korben Info - Cybersecurity');
        $source->setUrl('https://korben.info/categories/cybersecurite/');
        $source->setFormat(FormatEnum::HTML);
        $source->setCategory($this->getReference(CategoryFixture::CATEGORY_REFERENCE . '1', Category::class));
        $source->setStatus(StatusEnum::ACTIVE);
        $this->addReference(self::SOURCE_REFERENCE . 'cybersecurite', $source);
        $manager->persist($source);

        $source = new Source();
        $source->setName('Invalid Source');
        $source->setUrl('https://invaliddomain.com/feed');
        $source->setFormat(FormatEnum::HTML);
        $source->setStatus(StatusEnum::INACTIVE);
        $manager->persist($source);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [CategoryFixture::class];
    }

    public static function getGroups(): array
    {
        return ['dev'];
    }
}
