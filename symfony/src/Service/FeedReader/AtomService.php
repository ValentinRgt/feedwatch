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

class AtomService extends AbstractFeedReader implements FeedReaderInterface
{
    private const string ATOM_NAMESPACE = 'http://www.w3.org/2005/Atom';

    /**
     * @inheritdoc
     */
    public function supports(FormatEnum $format): bool
    {
        return FormatEnum::ATOM === $format;
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

        $namespaces = $content->getNamespaces(true);
        $entries = in_array(self::ATOM_NAMESPACE, $namespaces, true)
            ? $content->children(self::ATOM_NAMESPACE)->entry
            : $content->entry;

        foreach ($entries as $entry) {
            $title = trim((string) $entry->title);
            $link = $this->extractLink($entry);
            $published = (string) $entry->published;

            $articleDTO = new ArticleDTO();
            $articleDTO->checksum = $this->checksum($title . $link);
            $articleDTO->title = $title;
            $articleDTO->link = $link;
            $articleDTO->publishedAt = $this->parseDate($published);
            $items[] = $articleDTO;
        }

        return $items;
    }

    private function extractLink(SimpleXMLElement $entry): string
    {
        $fallback = '';

        foreach ($entry->link as $link) {
            $attributes = $link->attributes();
            $href = trim((string) ($attributes->href ?? ''));
            if ($href === '') {
                continue;
            }

            $rel = (string) ($attributes->rel ?? '');
            if ($rel === '' || $rel === 'alternate') {
                return $href;
            }

            if ($fallback === '') {
                $fallback = $href;
            }
        }

        return $fallback;
    }
}
