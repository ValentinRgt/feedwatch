<?php

declare(strict_types=1);

namespace App\EntityListener;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsEntityListener(event: Events::prePersist, method: 'onPrePersist', entity: Category::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'onPreUpdate', entity: Category::class)]
readonly class CategoryListener
{
    public function __construct(
        private readonly SluggerInterface $slugger
    ) {
    }

    /**
     * @param Category $category
     * @return void
     */
    public function onPrePersist(Category $category): void
    {
        if ($category->getSlug() === null) {
            $category->setSlug(
                $this->slugger->slug($category->getName())->lower()->toString()
            );
        }
    }

    /**
     * @param Category $category
     * @param PreUpdateEventArgs $event
     * @return void
     */
    public function onPreUpdate(Category $category, PreUpdateEventArgs $event): void
    {
        if (isset($event->getEntityChangeSet()['name'])) {
            if ($event->getEntityChangeSet()['name'][0] !== $event->getEntityChangeSet()['name'][1]) {
                $category->setSlug(
                    $this->slugger->slug($category->getName())->lower()->toString()
                );
            }
        }
    }
}
