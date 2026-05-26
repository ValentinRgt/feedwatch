<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\FeedReader;

use App\Entity\Source;
use App\Enum\FormatEnum;
use App\Service\FeedReader\HTMLService;
use App\Tests\Support\UnitTester;
use InvalidArgumentException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class HTMLServiceCest
{
    private const string PAGE = <<<HTML
        <html>
            <body>
                <article class="article-card">
                    <h2 class="article-card-title">First article</h2>
                    <a class="article-card-link">https://feedwatch.local/1</a>
                    <time class="published-at">18 May 2026 09:30:00 +0000</time>
                </article>
                <article class="article-card">
                    <h2 class="article-card-title">Second article</h2>
                    <a class="article-card-link">https://feedwatch.local/2</a>
                    <time class="published-at">19 May 2026 10:00:00 +0000</time>
                </article>
            </body>
        </html>
        HTML;

    private function service(string $body): HTMLService
    {
        return new HTMLService(new MockHttpClient(new MockResponse($body)));
    }

    private function source(
        ?string $checksum = null,
        bool $withSelectors = true,
        bool $withPublishedAt = true,
    ): Source {
        $source = (new Source())->setUrl('https://feedwatch.local/page');
        $source->setFormat(FormatEnum::HTML);
        $source->setChecksum($checksum);

        if ($withSelectors) {
            $source->setItemContainer('//article[@class="article-card"]');
            $source->setItemTitle('.//h2[@class="article-card-title"]');
            $source->setItemLink('.//a[@class="article-card-link"]');

            if ($withPublishedAt) {
                $source->setItemPublishedAt('.//time[@class="published-at"]');
            }
        }

        return $source;
    }

    public function supportsReturnsTrueOnlyForHtml(UnitTester $I): void
    {
        $service = $this->service('<html></html>');

        $I->assertTrue($service->supports(FormatEnum::HTML));
        $I->assertFalse($service->supports(FormatEnum::XML));
    }

    public function readReturnsNullWhenTheChecksumHasNotChanged(UnitTester $I): void
    {
        $service = $this->service(self::PAGE);

        $I->assertNull($service->read($this->source(hash('sha512', self::PAGE))));
    }

    public function readThrowsWhenRequiredItemSelectorsAreMissing(UnitTester $I): void
    {
        $service = $this->service(self::PAGE);

        $I->expectThrowable(
            InvalidArgumentException::class,
            fn () => $service->read($this->source(null, withSelectors: false)),
        );
    }

    public function readReturnsTheChecksumAndParsedItemsForAFreshPage(UnitTester $I): void
    {
        $service = $this->service(self::PAGE);

        $result = $service->read($this->source(null));

        $I->assertIsArray($result);
        $I->assertSame(hash('sha512', self::PAGE), $result['checksum']);
        $I->assertCount(2, $result['items']);
    }

    public function readMapsEachItemToAnArticleDto(UnitTester $I): void
    {
        $service = $this->service(self::PAGE);

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

    public function readKeepsPublishedAtNullWhenNoSelectorIsConfigured(UnitTester $I): void
    {
        $service = $this->service(self::PAGE);

        $result = $service->read($this->source(null, withPublishedAt: false));

        $I->assertNull($result['items'][0]->publishedAt);
        $I->assertNull($result['items'][1]->publishedAt);
    }
}
