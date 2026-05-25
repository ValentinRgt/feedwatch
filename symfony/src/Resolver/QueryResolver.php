<?php

declare(strict_types=1);

namespace App\Resolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[AsTargetedValueResolver('query')]
final readonly class QueryResolver implements ValueResolverInterface
{
    /**
     * @inheritdoc
     * @return iterable<string>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $value = $request->query->getString('q');

        yield $value;
    }
}
