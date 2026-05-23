<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Source;
use App\Entity\SourceError;
use App\Enum\FormatEnum;
use App\Enum\StatusEnum;
use App\Interface\FeedReaderInterface;
use App\Repository\SourceErrorRepository;
use App\Repository\SourceRepository;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Throwable;

class SourceService
{
    /**
     * @param iterable<FeedReaderInterface> $feedReaders
     */
    public function __construct(
        #[AutowireIterator(FeedReaderInterface::class)]
        private readonly iterable $feedReaders,
        private readonly SourceRepository $sourceRepository,
        private readonly SourceErrorRepository $sourceErrorRepository,
    ) {
    }

    /**
     * @param FormatEnum $format
     * @return FeedReaderInterface
     */
    public function getReader(FormatEnum $format): FeedReaderInterface
    {
        foreach ($this->feedReaders as $feedReader) {
            if ($feedReader->supports($format)) {
                return $feedReader;
            }
        }

        throw new RuntimeException(sprintf('No feed reader supports format "%s".', $format->value));
    }

    /**
     * @param Source $source
     * @return void
     */
    public function updateSource(Source $source): void
    {
        $this->sourceRepository->save($source, true);
    }

    /**
     * @param Source $source
     * @param Throwable $throwable
     * @return void
     */
    public function recordFailure(Source $source, Throwable $throwable): void
    {
        $error = new SourceError();
        $error->setSource($source);
        $error->setExceptionClass($throwable::class);
        $error->setMessage($throwable->getMessage());
        $error->setFile($throwable->getFile());
        $error->setLine($throwable->getLine());

        $this->sourceErrorRepository->save($error, true);

        $source->setStatus(StatusEnum::IN_ERROR);
        $this->sourceRepository->save($source, true);
    }
}
