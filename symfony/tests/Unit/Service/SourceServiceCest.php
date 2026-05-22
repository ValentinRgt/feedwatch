<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Source;
use App\Enum\FormatEnum;
use App\Interface\FeedReaderInterface;
use App\Repository\SourceRepository;
use App\Service\SourceService;
use App\Tests\Support\UnitTester;
use Codeception\Stub;
use RuntimeException;

class SourceServiceCest
{
    /**
     * @param FormatEnum ...$supportedFormats Formats this reader claims to support.
     */
    private function reader(FormatEnum ...$supportedFormats): FeedReaderInterface
    {
        /** @var FeedReaderInterface $reader */
        $reader = Stub::makeEmpty(FeedReaderInterface::class, [
            'supports' => fn (FormatEnum $format): bool => in_array($format, $supportedFormats, true),
        ]);

        return $reader;
    }

    public function getReaderReturnsTheReaderSupportingTheFormat(UnitTester $I): void
    {
        $htmlReader = $this->reader(FormatEnum::HTML);
        $xmlReader = $this->reader(FormatEnum::XML);

        $service = new SourceService([$htmlReader, $xmlReader], $this->repository());

        $I->assertSame($xmlReader, $service->getReader(FormatEnum::XML));
        $I->assertSame($htmlReader, $service->getReader(FormatEnum::HTML));
    }

    public function getReaderReturnsTheFirstMatchingReader(UnitTester $I): void
    {
        $first = $this->reader(FormatEnum::XML);
        $second = $this->reader(FormatEnum::XML);

        $service = new SourceService([$first, $second], $this->repository());

        $I->assertSame($first, $service->getReader(FormatEnum::XML));
    }

    public function getReaderThrowsWhenNoReaderSupportsTheFormat(UnitTester $I): void
    {
        $service = new SourceService([$this->reader(FormatEnum::HTML)], $this->repository());

        $I->expectThrowable(
            new RuntimeException('No feed reader supports format "xml".'),
            fn () => $service->getReader(FormatEnum::XML),
        );
    }

    public function getReaderThrowsWhenNoReaderIsRegistered(UnitTester $I): void
    {
        $service = new SourceService([], $this->repository());

        $I->expectThrowable(
            RuntimeException::class,
            fn () => $service->getReader(FormatEnum::HTML),
        );
    }

    public function updateSourcePersistsTheSourceThroughTheRepository(UnitTester $I): void
    {
        $captured = [];
        /** @var SourceRepository $repository */
        $repository = Stub::makeEmpty(SourceRepository::class, [
            'save' => function (Source $source, bool $flush = false) use (&$captured): void {
                $captured[] = [$source, $flush];
            },
        ]);
        $service = new SourceService([], $repository);
        $source = (new Source())->setName('Example');

        $service->updateSource($source);

        $I->assertCount(1, $captured);
        $I->assertSame($source, $captured[0][0]);
        $I->assertTrue($captured[0][1], 'updateSource() must flush the change.');
    }

    private function repository(): SourceRepository
    {
        /** @var SourceRepository $repository */
        $repository = Stub::makeEmpty(SourceRepository::class);

        return $repository;
    }
}
