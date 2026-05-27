<?php

declare(strict_types=1);

namespace App\Fixture\Data;

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
    public const string SOURCE_REFERENCE = 'source_';

    public function load(ObjectManager $manager): void
    {
        $source = new Source();
        $source->setName('dev.to - Web Development');
        $source->setUrl('https://dev.to/feed/tag/webdev');
        $source->setFormat(FormatEnum::XML);
        $source->setCategory($this->getReference(CategoryFixture::CATEGORY_REFERENCE . '0', Category::class));
        $source->setStatus(StatusEnum::ACTIVE);
        $source->setPeriodicity(PeriodicityEnum::EVERY_15_MINUTES);
        $this->addReference(self::SOURCE_REFERENCE . '0', $source);
        $manager->persist($source);

        $source = new Source();
        $source->setName('Korben Info - Cybersecurity');
        $source->setUrl('https://korben.info/categories/cybersecurite/actualites-securite/');
        $source->setFormat(FormatEnum::HTML);
        $source->setPeriodicity(PeriodicityEnum::EVERY_15_MINUTES);
        $source->setCategory($this->getReference(CategoryFixture::CATEGORY_REFERENCE . '1', Category::class));
        $source->setStatus(StatusEnum::ACTIVE);
        $source->setItemContainer('.//article[contains(concat(" ", normalize-space(@class), " "), " article-card ")]');
        $source->setItemTitle('.//h2[contains(concat(" ", normalize-space(@class), " "), " article-card-title ")]');
        $source->setItemLink(
            './/h2[contains(concat(" ", normalize-space(@class), " "), " article-card-title ")]//a/@href'
        );
        $source->setItemPublishedAt('.//time[@itemprop="datePublished"]/@datetime');
        $this->addReference(self::SOURCE_REFERENCE . 'cybersecurite', $source);
        $manager->persist($source);

        $source = new Source();
        $source->setName('Grafikart - YouTube');
        $source->setUrl('https://www.youtube.com/feeds/videos.xml?channel_id=UCj_iGliGCkLcHSZ8eqVNPDQ');
        $source->setFormat(FormatEnum::ATOM);
        $source->setCategory($this->getReference(CategoryFixture::CATEGORY_REFERENCE . '0', Category::class));
        $source->setStatus(StatusEnum::ACTIVE);
        $source->setPeriodicity(PeriodicityEnum::EVERY_6_HOURS);
        $this->addReference(self::SOURCE_REFERENCE . 'grafikart', $source);
        $manager->persist($source);

        $source = new Source();
        $source->setName('Invalid Source');
        $source->setUrl('https://invaliddomain.com/feed');
        $source->setFormat(FormatEnum::HTML);
        $source->setPeriodicity(PeriodicityEnum::DAILY);
        $source->setStatus(StatusEnum::INACTIVE);
        $this->addReference(self::SOURCE_REFERENCE . 'invalid', $source);
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
