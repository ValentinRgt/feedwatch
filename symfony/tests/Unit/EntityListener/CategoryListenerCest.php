<?php

declare(strict_types=1);

namespace App\Tests\Unit\EntityListener;

use App\Entity\Category;
use App\EntityListener\CategoryListener;
use App\Tests\Support\UnitTester;
use Codeception\Stub;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\String\Slugger\AsciiSlugger;

class CategoryListenerCest
{
    private function listener(): CategoryListener
    {
        return new CategoryListener(new AsciiSlugger());
    }

    /**
     * @param array<string, array{0: mixed, 1: mixed}> $changeSet
     */
    private function preUpdateArgs(Category $category, array $changeSet): PreUpdateEventArgs
    {
        /** @var EntityManagerInterface $em */
        $em = Stub::makeEmpty(EntityManagerInterface::class);

        return new PreUpdateEventArgs($category, $em, $changeSet);
    }

    public function prePersistGeneratesSlugFromName(UnitTester $I): void
    {
        $category = (new Category())->setName('Tech News');

        $this->listener()->onPrePersist($category);

        $I->assertSame('tech-news', $category->getSlug());
    }

    public function prePersistKeepsAnAlreadyDefinedSlug(UnitTester $I): void
    {
        $category = (new Category())
            ->setName('Tech News')
            ->setSlug('custom-slug');

        $this->listener()->onPrePersist($category);

        $I->assertSame('custom-slug', $category->getSlug());
    }

    public function preUpdateRegeneratesSlugWhenNameChanges(UnitTester $I): void
    {
        $category = (new Category())
            ->setName('Sport & Loisirs')
            ->setSlug('old-slug');

        $args = $this->preUpdateArgs($category, [
            'name' => ['Old Name', 'Sport & Loisirs'],
        ]);

        $this->listener()->onPreUpdate($category, $args);

        $I->assertSame('sport-loisirs', $category->getSlug());
    }

    public function preUpdateDoesNotTouchSlugWhenNameIsUnchanged(UnitTester $I): void
    {
        $category = (new Category())
            ->setName('Tech News')
            ->setSlug('old-slug');

        $args = $this->preUpdateArgs($category, [
            'name' => ['Tech News', 'Tech News'],
        ]);

        $this->listener()->onPreUpdate($category, $args);

        $I->assertSame('old-slug', $category->getSlug());
    }

    public function preUpdateDoesNotTouchSlugWhenNameIsNotInChangeSet(UnitTester $I): void
    {
        $category = (new Category())
            ->setName('Tech News')
            ->setSlug('old-slug');

        $args = $this->preUpdateArgs($category, [
            'slug' => ['old-slug', 'manually-edited'],
        ]);

        $this->listener()->onPreUpdate($category, $args);

        $I->assertSame('old-slug', $category->getSlug());
    }
}
