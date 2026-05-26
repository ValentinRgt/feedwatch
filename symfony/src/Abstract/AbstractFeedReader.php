<?php

declare(strict_types=1);

namespace App\Abstract;

use App\DTO\ArticleDTO;
use DateTimeImmutable;
use Exception;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class AbstractFeedReader
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * @param string $url
     * @return string
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    protected function fetch(string $url): string
    {
        $response = $this->httpClient->request(
            'GET',
            $url,
            [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (compatible; FeedWatch/1.0; +https://github.com/ValentinRgt/feedwatch)',
                ],
                'timeout' => 10,
            ]
        );

        return $response->getContent();
    }

    /**
     * @param string $content
     * @return string
     */
    protected function checksum(string $content): string
    {
        return hash('sha512', $content);
    }

    /**
     * @param string|null $value
     * @return DateTimeImmutable|null
     */
    protected function parseDate(?string $value): ?DateTimeImmutable
    {
        $value = trim((string) $value);

        if (empty($value)) {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * @param mixed $content
     * @return array<int, ArticleDTO>
     */
    abstract protected function parseItems(mixed $content): array;
}
