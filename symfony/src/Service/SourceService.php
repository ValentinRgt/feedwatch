<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Source;
use App\Enum\FormatEnum;
use App\Interface\FeedReaderInterface;
use App\Repository\SourceRepository;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class SourceService
{
    /**
     * @param iterable<FeedReaderInterface> $feedReaders
     */
    public function __construct(
        #[AutowireIterator(FeedReaderInterface::class)]
        private readonly iterable $feedReaders,
        private readonly SourceRepository $sourceRepository,
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
}
