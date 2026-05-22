<?php

declare(strict_types=1);

namespace App\Service\FeedReader;

use App\Abstract\AbstractFeedReader;
use App\Entity\Source;
use App\Enum\FormatEnum;
use App\Interface\FeedReaderInterface;

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

        return null;
    }

    /**
     * @inheritdoc
     */
    protected function parseItems($html): array
    {
        return [];
    }
}
