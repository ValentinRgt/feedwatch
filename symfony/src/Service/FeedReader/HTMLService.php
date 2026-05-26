<?php

declare(strict_types=1);

namespace App\Service\FeedReader;

use App\Abstract\AbstractFeedReader;
use App\DTO\ArticleDTO;
use App\Entity\Source;
use App\Enum\FormatEnum;
use App\Interface\FeedReaderInterface;
use InvalidArgumentException;
use Symfony\Component\DomCrawler\Crawler;

class HTMLService extends AbstractFeedReader implements FeedReaderInterface
{
    /**
     * @inheritdoc
     */
    public function supports(FormatEnum $format): bool
    {
        return FormatEnum::HTML === $format;
    }

    /**
     * @inheritdoc
     */
    public function read(Source $source): ?array
    {
        $content = $this->fetch($source->getUrl());
        $sourceChecksum = $this->checksum($content);

        if ($source->getChecksum() === $sourceChecksum) {
            return null;
        }

        if (!$source->hasRequiredItemSelectors()) {
            throw new InvalidArgumentException('The source is missing required item selectors for HTML parsing.');
        }

        $crawler = new Crawler($content);
        $items = $crawler->filterXPath($source->getItemContainer())->each(function (Crawler $node) use ($source) {
            return [
                'title' => $node->filterXPath($source->getItemTitle())->text('<<empty>>'),
                'link' => $node->filterXPath($source->getItemLink())->text('<<empty>>'),
                'publishedAt' => $source->getItemPublishedAt() ?
                    $node->filterXPath($source->getItemPublishedAt())->text('<<empty>>')
                    : null,
            ];
        });

        return [
            "checksum" => $sourceChecksum,
            "items" => $this->parseItems($items)
        ];
    }

    /**
     * @inheritdoc
     */
    protected function parseItems(mixed $content): array
    {
        $items = [];

        foreach ($content as $item) {
            $articleDTO = new ArticleDTO();
            $articleDTO->checksum = $this->checksum((string) $item['title'] . (string) $item['link']);
            $articleDTO->title = (string) $item['title'];
            $articleDTO->link = (string) $item['link'];
            $articleDTO->publishedAt = $this->parseDate((string) $item['publishedAt']);
            $items[] = $articleDTO;
        }

        return $items;
    }
}
