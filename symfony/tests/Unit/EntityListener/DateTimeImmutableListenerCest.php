<?php

declare(strict_types=1);

namespace App\Tests\Unit\EntityListener;

use App\EntityListener\DateTimeImmutableListener;
use App\Tests\Support\UnitTester;
use Codeception\Stub;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\ObjectManager;
use stdClass;

class DateTimeImmutableListenerCest
{
    private function prePersistArgs(object $entity): PrePersistEventArgs
    {
        /** @var ObjectManager $om */
        $om = Stub::makeEmpty(ObjectManager::class);

        return new PrePersistEventArgs($entity, $om);
    }

    private function preUpdateArgs(object $entity): PreUpdateEventArgs
    {
        /** @var EntityManagerInterface $em */
        $em = Stub::makeEmpty(EntityManagerInterface::class);
        $changeSet = [];

        return new PreUpdateEventArgs($entity, $em, $changeSet);
    }

    public function prePersistSetsCreatedAtOnSupportedEntities(UnitTester $I): void
    {
        $entity = new class {
            public ?\DateTimeImmutable $createdAt = null;

            public function setCreatedAt(\DateTimeImmutable $value): void
            {
                $this->createdAt = $value;
            }
        };

        (new DateTimeImmutableListener())->prePersist($this->prePersistArgs($entity));

        $I->assertInstanceOf(\DateTimeImmutable::class, $entity->createdAt);
    }

    public function preUpdateSetsUpdatedAtOnSupportedEntities(UnitTester $I): void
    {
        $entity = new class {
            public ?\DateTimeImmutable $updatedAt = null;

            public function setUpdatedAt(\DateTimeImmutable $value): void
            {
                $this->updatedAt = $value;
            }
        };

        (new DateTimeImmutableListener())->preUpdate($this->preUpdateArgs($entity));

        $I->assertInstanceOf(\DateTimeImmutable::class, $entity->updatedAt);
    }

    public function prePersistIgnoresEntitiesWithoutSetCreatedAt(UnitTester $I): void
    {
        (new DateTimeImmutableListener())->prePersist($this->prePersistArgs(new stdClass()));

        // No exception means unsupported entities are safely ignored.
        $I->assertTrue(true);
    }

    public function preUpdateIgnoresEntitiesWithoutSetUpdatedAt(UnitTester $I): void
    {
        (new DateTimeImmutableListener())->preUpdate($this->preUpdateArgs(new stdClass()));

        $I->assertTrue(true);
    }
}
