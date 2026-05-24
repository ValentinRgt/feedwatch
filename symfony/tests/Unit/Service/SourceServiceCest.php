<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Source;
use App\Entity\SourceError;
use App\Enum\FormatEnum;
use App\Enum\StatusEnum;
use App\Interface\FeedReaderInterface;
use App\Repository\SourceErrorRepository;
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

        $service = new SourceService([$htmlReader, $xmlReader], $this->repository(), $this->errorRepository());

        $I->assertSame($xmlReader, $service->getReader(FormatEnum::XML));
        $I->assertSame($htmlReader, $service->getReader(FormatEnum::HTML));
    }

    public function getReaderReturnsTheFirstMatchingReader(UnitTester $I): void
    {
        $first = $this->reader(FormatEnum::XML);
        $second = $this->reader(FormatEnum::XML);

        $service = new SourceService([$first, $second], $this->repository(), $this->errorRepository());

        $I->assertSame($first, $service->getReader(FormatEnum::XML));
    }

    public function getReaderThrowsWhenNoReaderSupportsTheFormat(UnitTester $I): void
    {
        $service = new SourceService(
            [$this->reader(FormatEnum::HTML)],
            $this->repository(),
            $this->errorRepository(),
        );

        $I->expectThrowable(
            new RuntimeException('No feed reader supports format "xml".'),
            fn () => $service->getReader(FormatEnum::XML),
        );
    }

    public function getReaderThrowsWhenNoReaderIsRegistered(UnitTester $I): void
    {
        $service = new SourceService([], $this->repository(), $this->errorRepository());

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
        $service = new SourceService([], $repository, $this->errorRepository());
        $source = (new Source())->setName('Example');

        $service->updateSource($source);

        $I->assertCount(1, $captured);
        $I->assertSame($source, $captured[0][0]);
        $I->assertTrue($captured[0][1], 'updateSource() must flush the change.');
    }

    public function recordFailurePersistsAnErrorAndMarksTheSourceInError(UnitTester $I): void
    {
        $savedSources = [];
        /** @var SourceRepository $repository */
        $repository = Stub::makeEmpty(SourceRepository::class, [
            'save' => function (Source $source, bool $flush = false) use (&$savedSources): void {
                $savedSources[] = [$source, $flush];
            },
        ]);

        $savedErrors = [];
        /** @var SourceErrorRepository $errorRepository */
        $errorRepository = Stub::makeEmpty(SourceErrorRepository::class, [
            'save' => function (SourceError $error, bool $flush = false) use (&$savedErrors): void {
                $savedErrors[] = [$error, $flush];
            },
        ]);

        $service = new SourceService([], $repository, $errorRepository);
        $source = (new Source())->setName('Failing');
        $source->setStatus(StatusEnum::ACTIVE);
        $throwable = new RuntimeException('Boom');

        $service->recordFailure($source, $throwable);

        $I->assertCount(1, $savedErrors, 'A SourceError must be persisted.');
        $I->assertTrue($savedErrors[0][1], 'The SourceError save must be flushed.');
        $error = $savedErrors[0][0];
        $I->assertSame($source, $error->getSource());
        $I->assertSame(RuntimeException::class, $error->getExceptionClass());
        $I->assertSame('Boom', $error->getMessage());
        $I->assertSame($throwable->getFile(), $error->getFile());
        $I->assertSame($throwable->getLine(), $error->getLine());

        $I->assertSame(StatusEnum::IN_ERROR, $source->getStatus());
        $I->assertCount(1, $savedSources, 'The Source must be persisted with its new status.');
        $I->assertSame($source, $savedSources[0][0]);
        $I->assertTrue($savedSources[0][1]);
    }

    private function repository(): SourceRepository
    {
        /** @var SourceRepository $repository */
        $repository = Stub::makeEmpty(SourceRepository::class);

        return $repository;
    }

    private function errorRepository(): SourceErrorRepository
    {
        /** @var SourceErrorRepository $repository */
        $repository = Stub::makeEmpty(SourceErrorRepository::class);

        return $repository;
    }
}
