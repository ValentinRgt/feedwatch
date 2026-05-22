<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\FeedReader;

use App\Entity\Source;
use App\Enum\FormatEnum;
use App\Service\FeedReader\XMLService;
use App\Tests\Support\UnitTester;
use RuntimeException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class XMLServiceCest
{
    private const string FEED = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0">
            <channel>
                <title>Tech feed</title>
                <item>
                    <title>First article</title>
                    <link>https://feedwatch.local/1</link>
                    <pubDate>18 May 2026 09:30:00 +0000</pubDate>
                </item>
                <item>
                    <title>Second article</title>
                    <link>https://feedwatch.local/2</link>
                    <pubDate>19 May 2026 10:00:00 +0000</pubDate>
                </item>
            </channel>
        </rss>
        XML;

    private function service(string $body): XMLService
    {
        return new XMLService(new MockHttpClient(new MockResponse($body)));
    }

    private function source(?string $checksum = null): Source
    {
        $source = (new Source())->setUrl('https://feedwatch.local/feed');
        $source->setFormat(FormatEnum::XML);
        $source->setChecksum($checksum);

        return $source;
    }

    public function supportsReturnsTrueOnlyForXml(UnitTester $I): void
    {
        $service = $this->service(self::FEED);

        $I->assertTrue($service->supports(FormatEnum::XML));
        $I->assertFalse($service->supports(FormatEnum::HTML));
    }

    public function readReturnsNullWhenTheChecksumHasNotChanged(UnitTester $I): void
    {
        $unchangedChecksum = hash('sha512', self::FEED);
        $service = $this->service(self::FEED);

        $I->assertNull($service->read($this->source($unchangedChecksum)));
    }

    public function readReturnsTheChecksumAndParsedItemsForAFreshFeed(UnitTester $I): void
    {
        $service = $this->service(self::FEED);

        $result = $service->read($this->source(null));

        $I->assertIsArray($result);
        $I->assertSame(hash('sha512', self::FEED), $result['checksum']);
        $I->assertCount(2, $result['items']);
    }

    public function readMapsEachItemToAnArticleDto(UnitTester $I): void
    {
        $service = $this->service(self::FEED);

        $result = $service->read($this->source(null));
        $first = $result['items'][0];

        $I->assertSame('First article', $first->title);
        $I->assertSame('https://feedwatch.local/1', $first->link);
        $I->assertSame(
            hash('sha512', 'First article' . 'https://feedwatch.local/1'),
            $first->checksum,
        );
        $I->assertSame('2026-05-18 09:30:00', $first->publishedAt->format('Y-m-d H:i:s'));
    }

    public function readThrowsWhenTheContentIsNotValidXml(UnitTester $I): void
    {
        $service = $this->service('this is definitely not xml');

        // SimpleXML emits libxml warnings on malformed input; keep them internal.
        $previous = libxml_use_internal_errors(true);

        try {
            $I->expectThrowable(
                RuntimeException::class,
                fn () => $service->read($this->source(null)),
            );
        } finally {
            libxml_use_internal_errors($previous);
        }
    }
}
