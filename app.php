<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
use Refactoring\Providers\ProvidersFactory;
use Refactoring\CommissionsProcessor;

if (empty($argv[1])) {
    die("USAGE: php $argv[0] input.txt\n");
}

$config = require __DIR__ . '/config.php';

try {
    $currencyRatesProvider = ProvidersFactory::createCurrencyRatesProvider($config);
    $binInfoProvider = ProvidersFactory::createBinInfoProvider($config);
    (new CommissionsProcessor($currencyRatesProvider, $binInfoProvider))->calculate($argv[1]);
} catch (Throwable $e) {
    var_dump($e->getMessage());
}