<?php
declare(strict_types=1);

namespace Refactoring\Providers\CurrencyRates;

interface CurrencyRatesProvider
{
    public function fetch(): array;
}