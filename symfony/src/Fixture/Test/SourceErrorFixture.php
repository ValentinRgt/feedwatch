<?php

declare(strict_types=1);

namespace App\Fixture\Test;

use App\Entity\Source;
use App\Entity\SourceError;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SourceErrorFixture extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $error = new SourceError();
        $source = $this->getReference(SourceFixture::SOURCE_REFERENCE . 'invalid', Source::class);
        $error->setSource($source);
        $error->setExceptionClass('TransportException');
        $error->setMessage('Could not resolve host: invaliddomain.com for "https://invaliddomain.com/feed".');
        $error->setFile('CommonResponseTrait.php');
        $error->setLine(140);
        $manager->persist($error);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [SourceFixture::class];
    }

    public static function getGroups(): array
    {
        return ['test'];
    }
}
