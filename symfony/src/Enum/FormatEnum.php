<?php

declare(strict_types=1);

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum FormatEnum: string implements TranslatableInterface
{
    case XML = 'xml';
    case ATOM = 'atom';
    case HTML = 'html';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans('format.' . $this->value, locale: $locale);
    }
}
