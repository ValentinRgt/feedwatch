<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\FeedReader;

use App\Entity\Source;
use App\Enum\FormatEnum;
use App\Service\FeedReader\HTMLService;
use App\Tests\Support\UnitTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class HTMLServiceCest
{
    private function service(string $body): HTMLService
    {
        return new HTMLService(new MockHttpClient(new MockResponse($body)));
    }

    private function source(?string $checksum = null): Source
    {
        $source = (new Source())->setUrl('https://feedwatch.local/page');
        $source->setFormat(FormatEnum::HTML);
        $source->setChecksum($checksum);

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
        $body = '<html><body>Same content</body></html>';
        $service = $this->service($body);

        $I->assertNull($service->read($this->source(hash('sha512', $body))));
    }

    /**
     * The HTML reader is currently a stub that never yields items; it returns
     * null even when the fetched content differs from the stored checksum.
     */
    public function readReturnsNullForChangedContentUntilParsingIsImplemented(UnitTester $I): void
    {
        $service = $this->service('<html><body>Brand new content</body></html>');

        $I->assertNull($service->read($this->source('an-old-and-different-checksum')));
    }
}
