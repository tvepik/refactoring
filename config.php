<?php

return [
    \Refactoring\Providers\ProvidersFactory::PROVIDER_CURRENCY_RATES => [
        'current' => 'external',
        'external' => [
            'url' => 'https://api.exchangeratesapi.io/latest',
            'authentication' => true,
            'access_key' => '',
        ]
    ],
    \Refactoring\Providers\ProvidersFactory::PROVIDER_BIN_INFO => [
        'current' => 'external',
        'external' => [
            'url' => 'https://lookup.binlist.net/',
        ]
    ]
];