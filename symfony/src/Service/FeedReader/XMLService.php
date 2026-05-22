<?php

declare(strict_types=1);

namespace App\Service\FeedReader;

use App\Abstract\AbstractFeedReader;
use App\DTO\ArticleDTO;
use App\Entity\Source;
use App\Enum\FormatEnum;
use App\Interface\FeedReaderInterface;
use RuntimeException;
use SimpleXMLElement;

class XMLService extends AbstractFeedReader implements FeedReaderInterface
{
    /**
     * @inheritdoc
     */
    public function supports(FormatEnum $format): bool
    {
        return FormatEnum::XML === $format;
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

        $xml = simplexml_load_string($content);

        if (!$xml instanceof SimpleXMLElement) {
            throw new RuntimeException('Unable to parse the feed content as XML.');
        }

        return [
            "checksum" => $sourceChecksum,
            "items" => $this->parseItems($xml)
        ];
    }

    /**
     * @inheritdoc
     */
    protected function parseItems(mixed $content): array
    {
        $items = [];

        foreach ($content->channel->item as $item) {
            $articleDTO = new ArticleDTO();
            $articleDTO->checksum = $this->checksum((string) $item->title . (string) $item->link);
            $articleDTO->title = (string) $item->title;
            $articleDTO->link = (string) $item->link;
            $articleDTO->publishedAt = $this->parseDate((string) $item->pubDate);
            $items[] = $articleDTO;
        }

        return $items;
    }
}
