<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\ArticleDTO;
use App\Entity\Article;
use App\Repository\ArticleRepository;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

class ArticleService
{
    public function __construct(
        private readonly ArticleRepository $articleRepository,
        private readonly ObjectMapperInterface $objectMapper,
    ) {
    }

    /**
     * @param array<int, ArticleDTO> $articles
     * @return void
     */
    public function createArticlesFromContent(array $articles): void
    {
        $checksums = array_map(fn (ArticleDTO $article) => $article->checksum, $articles);
        $existingChecksums = $this->articleRepository->findExistingChecksums($checksums);

        $articles = array_filter(
            $articles,
            fn (ArticleDTO $article) => !in_array($article->checksum, $existingChecksums, true),
        );

        foreach ($articles as $article) {
            $article = $this->objectMapper->map($article, Article::class);
            $this->articleRepository->save($article, true);
        }
    }
}
