<?php
declare(strict_types=1);

namespace Refactoring;

use Refactoring\Providers\BINInfo\BINInfoProvider;
use Refactoring\Providers\CurrencyRates\CurrencyRatesProvider;
use Exception;

class CommissionsProcessor
{
    private const COMMISSION_RATE_EU = 0.01;
    private const COMMISSION_RATE_NON_EU = 0.02;

    private $EU = [
        'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES',
        'FI', 'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU',
        'LV', 'MT', 'NL', 'PO', 'PT', 'RO', 'SE', 'SI', 'SK'
    ];

    public function __construct(
        private CurrencyRatesProvider $currencyRatesProvider,
        private BINInfoProvider $binInfoProvider
    ) {}

    public function calculate(string $input): void
    {
        $currencyRates = $this->currencyRatesProvider->fetch();
        foreach ($this->getTransactions($input) as $transaction) {
            try {
                ['bin' => $bin, 'amount' => $amount, 'currency' => $currency] = $transaction;

                $rate = $currencyRates['rates'][$currency] ?? 1;
                $fixedPart = $rate > 0 ? $amount / $rate : $amount;

                $commission = $fixedPart * $this->getVariablePart($bin);
                $commission = ceil($commission * 100) / 100; //is rounded up to cents
                var_dump($commission); //we are not doing anything with the result so far
            } catch (\ErrorException $e) {
                if (str_contains($e->getMessage(), '429 Too Many Requests')) {
                    //@todo should be marked as failed, and next time we'll try to calculate commissions for this transaction again
                    echo 'LOG: Calculation failed due to rate limiting. ' . json_encode($transaction) . "\n";
                } else {
                    throw $e;
                }
            }
        }
    }

    private function getVariablePart(string $bin): float
    {
        $binInfo = $this->binInfoProvider->fetch($bin);

        return in_array($binInfo['country']['alpha2'] ?? '', $this->EU, true)
            ? self::COMMISSION_RATE_EU : self::COMMISSION_RATE_NON_EU;
    }

    private function getTransactions(string $filename): \Generator
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new Exception('File does not exist or cannot be read: ' . $filename);
        }
        $file = fopen($filename, 'r');
        if (!$file) {
            throw new Exception('Unable to open file: ' . $file);
        }
        while (($line = fgets($file)) !== false) {
            $data = $this->extractData(trim($line));
            if (!empty($data)) {
                yield $data;
            }
        }
        fclose($file);
    }

    private function extractData(string $json): array
    {
        $data = json_decode($json, true);
        if (!$data) {
            echo "LOG: Invalid JSON: $json\n";
            return [];
        }
        if (!isset($data['bin'], $data['amount'], $data['currency'])) {
            echo "LOG: Missing required keys in JSON: $json\n";
            return [];
        }

        return $data;
    }
}