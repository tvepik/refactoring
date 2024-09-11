<?php
declare(strict_types=1);

namespace Refactoring\Providers;

use Refactoring\Providers\BINInfo\BINInfoProvider;
use Refactoring\Providers\BINInfo\ExternalBINInfoProvider;
use Refactoring\Providers\CurrencyRates\CurrencyRatesProvider;
use Refactoring\Providers\CurrencyRates\ExternalCurrencyRatesProvider;
use Exception;

class ProvidersFactory
{
    public const PROVIDER_CURRENCY_RATES = 'currency_rates';
    public const PROVIDER_BIN_INFO = 'bin_info';

    public static function createCurrencyRatesProvider(array $config): CurrencyRatesProvider
    {
        return self::createProvider(self::PROVIDER_CURRENCY_RATES, $config[self::PROVIDER_CURRENCY_RATES] ?? []);
    }

    public static function createBinInfoProvider(array $config): BINInfoProvider
    {
        return self::createProvider(self::PROVIDER_BIN_INFO, $config[self::PROVIDER_BIN_INFO] ?? []);
    }

    private static function createProvider(string $providerType, array $config): CurrencyRatesProvider|BINInfoProvider
    {
        $currentProvider = $config['current'] ?? null;
        $options = $config[$currentProvider] ?? [];

        return match ($providerType) {
            self::PROVIDER_CURRENCY_RATES => match ($currentProvider) {
                'external' => new ExternalCurrencyRatesProvider($options),
                default => throw new Exception('Unsupported currency rates provider: ' . $currentProvider),
            },
            self::PROVIDER_BIN_INFO => match ($currentProvider) {
                'external' => new ExternalBinInfoProvider($options),
                default => throw new Exception('Unsupported bin info provider: ' . $currentProvider),
            },
            default => throw new Exception('Unsupported provider type: ' . $providerType),
        };
    }
}