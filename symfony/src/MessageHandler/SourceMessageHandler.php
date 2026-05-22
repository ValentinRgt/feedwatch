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
final readonly class SourceMessageHandler
{
    public function __construct(
        private SourceRepository $sourceRepository,
        private SourceService $sourceService,
        private ArticleService $articleService,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param SourceMessage $message
     * @return void
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function __invoke(SourceMessage $message): void
    {
        $sources = $this->sourceRepository->findDueSources();

        foreach ($sources as $source) {
            $reader = $this->sourceService->getReader($source->getFormat());

            $content = $reader->read($source);

            if (null === $content) {
                $this->logger->info(sprintf('No new content for source "%s".', $source->getName()));
                continue;
            }

            $source->setChecksum($content['checksum']);
            $source->setLastFetchedAt(new DateTimeImmutable());
            $this->sourceService->updateSource($source);

            $this->articleService->createArticlesFromContent($content['items'], $source);
        }
    }
}
