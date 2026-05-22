<?php

declare(strict_types=1);

namespace App\Interface;

use App\DTO\ArticleDTO;
use App\Entity\Source;
use App\Enum\FormatEnum;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface FeedReaderInterface
{
    /**
     * @param FormatEnum $format
     * @return bool
     */
    public function supports(FormatEnum $format): bool;

    /**
     * @param Source $source
     * @return array<string, string|array<int, ArticleDTO>>|null
     */
    public function read(Source $source): ?array;
}
