<?php

declare(strict_types=1);

namespace App\Enum;

use DateInterval;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum PeriodicityEnum: string implements TranslatableInterface
{
    case EVERY_15_MINUTES = 'every_15_minutes';
    case EVERY_30_MINUTES = 'every_30_minutes';
    case HOURLY = 'hourly';
    case EVERY_6_HOURS = 'every_6_hours';
    case EVERY_12_HOURS = 'every_12_hours';
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';

    public function interval(): DateInterval
    {
        return new DateInterval(match ($this) {
            self::EVERY_15_MINUTES => 'PT15M',
            self::EVERY_30_MINUTES => 'PT30M',
            self::HOURLY => 'PT1H',
            self::EVERY_6_HOURS => 'PT6H',
            self::EVERY_12_HOURS => 'PT12H',
            self::DAILY => 'P1D',
            self::WEEKLY => 'P1W',
            self::MONTHLY => 'P1M',
        });
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans('periodicity.' . $this->value, locale: $locale);
    }
}
