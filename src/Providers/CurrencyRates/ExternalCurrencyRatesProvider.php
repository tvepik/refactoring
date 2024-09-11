<?php
declare(strict_types=1);

namespace Refactoring\Providers\CurrencyRates;

use Refactoring\Providers\ExternalProvider;
use Exception;

class ExternalCurrencyRatesProvider extends ExternalProvider implements CurrencyRatesProvider
{
    public function fetch(): array
    {
        return $this->fetchData($this->getUrl());
    }

    protected function handleResponse(array $data): array
    {
        if ($data['success'] ?? false) {
            return $data;
        }
        throw new Exception($data['error']['info'] ?? 'Unknown error');
    }

    private function getUrl(): string
    {
        return $this->getOption('url') .
            ($this->options['authentication'] ? '?access_key=' . $this->getOption('access_key') : '');
    }
}