<?php

declare(strict_types=1);

namespace App\Tests\Unit\Abstract;

use App\Abstract\AbstractFeedReader;
use App\Tests\Support\UnitTester;
use DateTimeImmutable;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AbstractFeedReaderCest
{
    /**
     * Concrete subclass exposing the protected helpers of AbstractFeedReader.
     */
    private function reader(HttpClientInterface $httpClient): object
    {
        return new class ($httpClient) extends AbstractFeedReader {
            public function exposedFetch(string $url): string
            {
                return $this->fetch($url);
            }

            public function exposedChecksum(string $content): string
            {
                return $this->checksum($content);
            }

            public function exposedParseDate(?string $value): ?DateTimeImmutable
            {
                return $this->parseDate($value);
            }

            protected function parseItems(mixed $content): array
            {
                return [];
            }
        };
    }

    private function emptyClient(): HttpClientInterface
    {
        return new MockHttpClient();
    }

    public function checksumIsTheSha512OfTheContent(UnitTester $I): void
    {
        $reader = $this->reader($this->emptyClient());

        $I->assertSame(hash('sha512', 'feedwatch'), $reader->exposedChecksum('feedwatch'));
    }

    public function checksumDiffersForDifferentContent(UnitTester $I): void
    {
        $reader = $this->reader($this->emptyClient());

        $I->assertNotSame($reader->exposedChecksum('a'), $reader->exposedChecksum('b'));
    }

    public function fetchReturnsTheHttpResponseBody(UnitTester $I): void
    {
        $reader = $this->reader(new MockHttpClient(new MockResponse('<rss>payload</rss>')));

        $I->assertSame('<rss>payload</rss>', $reader->exposedFetch('https://feedwatch.local/feed'));
    }

    public function parseDateReturnsNullForNull(UnitTester $I): void
    {
        $reader = $this->reader($this->emptyClient());

        $I->assertNull($reader->exposedParseDate(null));
    }

    public function parseDateReturnsNullForBlankStrings(UnitTester $I): void
    {
        $reader = $this->reader($this->emptyClient());

        $I->assertNull($reader->exposedParseDate(''));
        $I->assertNull($reader->exposedParseDate('   '));
    }

    public function parseDateReturnsNullForUnparseableValues(UnitTester $I): void
    {
        $reader = $this->reader($this->emptyClient());

        $I->assertNull($reader->exposedParseDate('definitely-not-a-date'));
    }

    public function parseDateParsesAValidRfcDate(UnitTester $I): void
    {
        $reader = $this->reader($this->emptyClient());

        $parsed = $reader->exposedParseDate('18 May 2026 09:30:00 +0000');

        $I->assertInstanceOf(DateTimeImmutable::class, $parsed);
        $I->assertSame('2026-05-18 09:30:00', $parsed->format('Y-m-d H:i:s'));
    }
}
