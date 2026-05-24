<?php

declare(strict_types=1);

namespace App\Resolver;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[AsTargetedValueResolver('pageSize')]
final readonly class PageSizeResolver implements ValueResolverInterface
{
    /**
     * @param int[] $options
     */
    public function __construct(
        #[Autowire('%items_per_page%')]
        private array $options,
    ) {
    }

    /**
     * @inheritdoc
     * @return iterable<int>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $value = $request->query->getInt('pageSize', $this->options[0]);

        yield in_array($value, $this->options, true) ? $value : $this->options[0];
    }
}
