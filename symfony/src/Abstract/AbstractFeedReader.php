<?php

declare(strict_types=1);

namespace App\Abstract;

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
     */
    protected function fetch(string $url): string
    {
        $response = $this->httpClient->request('GET', $url);

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
     * @param mixed $xml
     * @return array
     */
    abstract protected function parseItems($xml): array;
}
