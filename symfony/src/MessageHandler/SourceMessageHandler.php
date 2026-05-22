<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Interface\FeedReaderInterface;
use App\Message\SourceMessage;
use App\Repository\SourceRepository;
use App\Service\ArticleService;
use App\Service\SourceService;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SourceMessageHandler
{
    public function __construct(
        private readonly SourceRepository $sourceRepository,
        private readonly SourceService $sourceService,
        private readonly ArticleService $articleService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(SourceMessage $message): void
    {
        $sources = $this->sourceRepository->findDueSources();

        foreach ($sources as $source) {
            /** @var FeedReaderInterface $reader */
            $reader = $this->sourceService->getReader($source->getFormat());

            $content = $reader->read($source);

            if (null === $content) {
                $this->logger->info(sprintf('No new content for source "%s".', $source->getName()));
                continue;
            }

            if ($source->getChecksum() === $content['checksum']) {
                $this->logger->info(sprintf('Source "%s" has not changed since the last fetch.', $source->getName()));
                continue;
            }

            $source->setChecksum($content['checksum']);
            $source->setLastFetchedAt(new DateTimeImmutable());
            $this->sourceService->updateSource($source);

            $this->articleService->createArticlesFromContent($content['items']);
        }
    }
}
