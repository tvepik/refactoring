<?php
declare(strict_types=1);

if (empty($argv[1])) {
    die("USAGE: php $argv[0] input.txt\n");
}

try {
//    @todo Code should be extendable
    $currencyRates = fetchCurrencyRates();
    foreach (getTransactions($argv[1]) as $row) {
        $data = json_decode($row, true);
//        @todo check if JSON is invalid

        ['bin' => $bin, 'amount' => $amount, 'currency' => $currency] = $data;

        $rate = $currencyRates['rates'][$currency] ?? 1;
        $amntFixed = $rate > 0 ? $amount / $rate : $amount;

        $binInfo = fetchBinInfo($bin);
        $isEu = isEu($binInfo['country']['alpha2'] ?? '');
        var_dump($amntFixed * ($isEu ? 0.01 : 0.02));
//        @todo improvement: add ceiling of commissions by cents
    }
} catch (Throwable $e) {
    var_dump($e->getMessage());
}

function getTransactions(string $filename): Generator
{
    if (!file_exists($filename) || !is_readable($filename)) {
        throw new Exception('File does not exist or cannot be read: ' . $filename);
    }
    $file = fopen($filename, 'r');
    if (!$file) {
        throw new Exception('Unable to open file: ' . $file);
    }
    while (($line = fgets($file)) !== false) {
        yield trim($line);
    }
    fclose($file);
}

function fetchCurrencyRates(): array
{
//    @todo access_key
    return fetchData('https://api.exchangeratesapi.io/latest?access_key=');
}

function fetchBinInfo(string $bin): array
{
//    @todo Too Many Requests
    return fetchData("https://lookup.binlist.net/$bin");
}

function fetchData(string $url): array
{
    $response = file_get_contents($url);
    if (!$response) {
        throw new Exception('Failed to retrieve data');
    }

    return json_decode($response, true);
}

function isEu(string $countryCode): bool
{
    $euCountries = [
        'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES',
        'FI', 'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU',
        'LV', 'MT', 'NL', 'PO', 'PT', 'RO', 'SE', 'SI', 'SK'
    ];

    return in_array($countryCode, $euCountries, true);
}