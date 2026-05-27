<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\FeedReader;

use App\Entity\Source;
use App\Enum\FormatEnum;
use App\Service\FeedReader\AtomService;
use App\Tests\Support\UnitTester;
use RuntimeException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class AtomServiceCest
{
    private const string FEED = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <feed xmlns="http://www.w3.org/2005/Atom">
            <title>Tech feed</title>
            <entry>
                <title>PHP 8.1 reached its End-of-Life</title>
                <link href="https://php.watch/news/2025/12/php-81-eol"/>
                <id>https://php.watch/news/2025/12/php-81-eol</id>
                <updated>2025-12-31T10:44:00+00:00</updated>
                <published>2025-12-31T10:44:00+00:00</published>
                <summary>PHP 8.1 completed its life cycle.</summary>
            </entry>
            <entry>
                <id>yt:video:aCKXGNtulQQ</id>
                <title>Apprendre le CSS</title>
                <link rel="alternate" href="https://www.youtube.com/watch?v=aCKXGNtulQQ"/>
                <published>2026-05-11T13:03:00+00:00</published>
                <updated>2026-05-16T22:37:26+00:00</updated>
            </entry>
        </feed>
        XML;

    private const string FEED_WITHOUT_PUBLISHED = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <feed xmlns="http://www.w3.org/2005/Atom">
            <entry>
                <title>No published date here</title>
                <link href="https://feedwatch.local/no-date"/>
                <updated>2026-01-15T12:00:00+00:00</updated>
            </entry>
        </feed>
        XML;

    private const string FEED_WITH_MULTIPLE_LINKS = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <feed xmlns="http://www.w3.org/2005/Atom">
            <entry>
                <title>Entry with multiple links</title>
                <link rel="self" href="https://feedwatch.local/self"/>
                <link rel="alternate" href="https://feedwatch.local/alternate"/>
                <link rel="enclosure" href="https://feedwatch.local/enclosure"/>
            </entry>
        </feed>
        XML;

    private function service(string $body): AtomService
    {
        return new AtomService(new MockHttpClient(new MockResponse($body)));
    }

    private function source(?string $checksum = null): Source
    {
        $source = (new Source())->setUrl('https://feedwatch.local/feed');
        $source->setFormat(FormatEnum::ATOM);
        $source->setChecksum($checksum);

        return $source;
    }

    public function supportsReturnsTrueOnlyForAtom(UnitTester $I): void
    {
        $service = $this->service(self::FEED);

        $I->assertTrue($service->supports(FormatEnum::ATOM));
        $I->assertFalse($service->supports(FormatEnum::XML));
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

    public function readMapsEachEntryToAnArticleDto(UnitTester $I): void
    {
        $service = $this->service(self::FEED);

        $result = $service->read($this->source(null));
        $first = $result['items'][0];

        $I->assertSame('PHP 8.1 reached its End-of-Life', $first->title);
        $I->assertSame('https://php.watch/news/2025/12/php-81-eol', $first->link);
        $I->assertSame(
            hash('sha512', 'PHP 8.1 reached its End-of-Life' . 'https://php.watch/news/2025/12/php-81-eol'),
            $first->checksum,
        );
        $I->assertSame('2025-12-31 10:44:00', $first->publishedAt->format('Y-m-d H:i:s'));

        $second = $result['items'][1];
        $I->assertSame('Apprendre le CSS', $second->title);
        $I->assertSame('https://www.youtube.com/watch?v=aCKXGNtulQQ', $second->link);
        $I->assertSame('2026-05-11 13:03:00', $second->publishedAt->format('Y-m-d H:i:s'));
    }

    public function readLeavesPublishedAtNullWhenAbsent(UnitTester $I): void
    {
        $service = $this->service(self::FEED_WITHOUT_PUBLISHED);

        $result = $service->read($this->source(null));

        $I->assertCount(1, $result['items']);
        $I->assertNull($result['items'][0]->publishedAt);
    }

    public function readPrefersAlternateLinkWhenMultipleLinksArePresent(UnitTester $I): void
    {
        $service = $this->service(self::FEED_WITH_MULTIPLE_LINKS);

        $result = $service->read($this->source(null));

        $I->assertSame('https://feedwatch.local/alternate', $result['items'][0]->link);
    }

    public function readThrowsWhenTheContentIsNotValidXml(UnitTester $I): void
    {
        $service = $this->service('this is definitely not xml');

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
