<?php

declare(strict_types=1);

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum StatusEnum: string implements TranslatableInterface
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case IN_ERROR = 'in_error';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans('status.' . $this->value, locale: $locale);
    }
}
