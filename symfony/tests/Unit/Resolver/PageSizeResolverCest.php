<?php

declare(strict_types=1);

namespace App\Tests\Unit\Resolver;

use App\Resolver\PageSizeResolver;
use App\Tests\Support\UnitTester;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Exercises the targeted value resolver wired via #[ValueResolver('pageSize')].
 * The resolver only reads the `pageSize` query string and matches it against
 * the configured whitelist (%items_per_page%) — every branch must be covered
 * in isolation.
 */
class PageSizeResolverCest
{
    private const OPTIONS = [10, 20, 50, 100];

    public function returnsTheFirstOptionWhenTheQueryStringIsMissing(UnitTester $I): void
    {
        $resolver = new PageSizeResolver(self::OPTIONS);

        $resolved = $this->resolve($resolver, Request::create('/admin/source'));

        $I->assertSame([10], $resolved);
    }

    public function returnsTheValueWhenItIsInTheWhitelist(UnitTester $I): void
    {
        $resolver = new PageSizeResolver(self::OPTIONS);

        $I->assertSame([20], $this->resolve($resolver, Request::create('/admin/source?pageSize=20')));
        $I->assertSame([50], $this->resolve($resolver, Request::create('/admin/source?pageSize=50')));
        $I->assertSame([100], $this->resolve($resolver, Request::create('/admin/source?pageSize=100')));
    }

    public function fallsBackToTheFirstOptionWhenTheValueIsNotInTheWhitelist(UnitTester $I): void
    {
        $resolver = new PageSizeResolver(self::OPTIONS);

        $I->assertSame([10], $this->resolve($resolver, Request::create('/admin/source?pageSize=25')));
        $I->assertSame([10], $this->resolve($resolver, Request::create('/admin/source?pageSize=999')));
    }

    public function rejectsNonNumericValuesViaSymfonyRequest(UnitTester $I): void
    {
        $resolver = new PageSizeResolver(self::OPTIONS);

        // Request::getInt() rejects non-numeric strings strictly — that is Symfony's contract,
        // not the resolver's, but this test pins the user-visible behaviour: a typo in
        // ?pageSize=foo will surface as a 400 Bad Request before the resolver ever runs.
        $I->expectThrowable(
            BadRequestException::class,
            fn () => $this->resolve($resolver, Request::create('/admin/source?pageSize=foo')),
        );
        $I->expectThrowable(
            BadRequestException::class,
            fn () => $this->resolve($resolver, Request::create('/admin/source?pageSize=')),
        );
    }

    public function fallsBackToTheFirstOptionForNegativeOrZeroValues(UnitTester $I): void
    {
        $resolver = new PageSizeResolver(self::OPTIONS);

        $I->assertSame([10], $this->resolve($resolver, Request::create('/admin/source?pageSize=0')));
        $I->assertSame([10], $this->resolve($resolver, Request::create('/admin/source?pageSize=-20')));
    }

    public function respectsTheConfiguredOptionList(UnitTester $I): void
    {
        // The whitelist is injected from %items_per_page%; the resolver must honour any list.
        $resolver = new PageSizeResolver([25, 75]);

        $I->assertSame([25], $this->resolve($resolver, Request::create('/admin/source')));
        $I->assertSame([75], $this->resolve($resolver, Request::create('/admin/source?pageSize=75')));
        // 10 is not in this custom whitelist, fallback to the first option (25).
        $I->assertSame([25], $this->resolve($resolver, Request::create('/admin/source?pageSize=10')));
    }

    /**
     * @return list<int>
     */
    private function resolve(PageSizeResolver $resolver, Request $request): array
    {
        $argument = new ArgumentMetadata(
            name: 'pageSize',
            type: 'int',
            isVariadic: false,
            hasDefaultValue: false,
            defaultValue: null,
        );

        return iterator_to_array($resolver->resolve($request, $argument), false);
    }
}
