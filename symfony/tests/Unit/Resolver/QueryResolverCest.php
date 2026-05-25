<?php

declare(strict_types=1);

namespace App\Tests\Unit\Resolver;

use App\Resolver\QueryResolver;
use App\Tests\Support\UnitTester;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Exercises the targeted value resolver wired via #[ValueResolver('query')].
 * The resolver yields whatever `$request->query->getString('q')` returns —
 * each input shape must produce a deterministic, scalar string.
 */
class QueryResolverCest
{
    public function returnsAnEmptyStringWhenTheQueryParamIsMissing(UnitTester $I): void
    {
        $resolver = new QueryResolver();

        $I->assertSame([''], $this->resolve($resolver, Request::create('/admin/article')));
    }

    public function returnsTheTrimmedValueWhenTheQueryParamIsProvided(UnitTester $I): void
    {
        $resolver = new QueryResolver();

        $I->assertSame(['foo'], $this->resolve($resolver, Request::create('/admin/article?q=foo')));
        $I->assertSame(['hello world'], $this->resolve($resolver, Request::create('/admin/article?q=hello+world')));
    }

    public function returnsAnEmptyStringWhenTheQueryParamIsBlank(UnitTester $I): void
    {
        $resolver = new QueryResolver();

        $I->assertSame([''], $this->resolve($resolver, Request::create('/admin/article?q=')));
    }

    public function rejectsArrayQueryParameterViaSymfonyRequest(UnitTester $I): void
    {
        $resolver = new QueryResolver();

        // Request::getString() throws when the parameter is not scalar — that pins the
        // user-visible behaviour: ?q[]=foo will surface as a 400 before the resolver runs.
        $I->expectThrowable(
            BadRequestException::class,
            fn () => $this->resolve($resolver, Request::create('/admin/article?q[]=foo')),
        );
    }

    /**
     * @return list<string>
     */
    private function resolve(QueryResolver $resolver, Request $request): array
    {
        $argument = new ArgumentMetadata(
            name: 'query',
            type: 'string',
            isVariadic: false,
            hasDefaultValue: false,
            defaultValue: null,
        );

        return iterator_to_array($resolver->resolve($request, $argument), false);
    }
}
