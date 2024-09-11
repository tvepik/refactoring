<?php
declare(strict_types=1);

namespace Refactoring\Providers;

use Exception;
use Refactoring\Exceptions\HttpRequestException;

abstract class ExternalProvider
{
    public function __construct(protected readonly array $options) {}

    protected function fetchData(string $url): array
    {
        $context = stream_context_create(['http' => ['ignore_errors' => true]]);
        $response = file_get_contents($url, false, $context);
        if (str_contains($http_response_header[0] ?? '', '429 Too Many Requests')) {
            throw new HttpRequestException('Rate limit exceeded for ' . $url);
        }
        if (!$response) {
            throw new HttpRequestException('Failed to retrieve data from ' . $url);
        }

        return $this->handleResponse(json_decode($response, true, flags: JSON_THROW_ON_ERROR));
    }

    protected function handleResponse(array $data): array
    {
        return $data;
    }

    protected function getOption(string $key): mixed
    {
        if (!isset($this->options[$key])) {
            throw new Exception(static::class . ': Missing configuration key: ' . $key);
        }

        return $this->options[$key];
    }
}