<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\Article;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: Article::class)]
class ArticleDTO
{
    public ?string $checksum = null;
    public ?string $title = null;
    public ?string $link = null;
    public ?string $description = null;
}
