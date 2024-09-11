<?php
declare(strict_types=1);

namespace Refactoring\Providers;

use Exception;

abstract class ExternalProvider
{
    public function __construct(protected readonly array $options) {}

    protected function fetchData(string $url): array
    {
        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
            if (error_reporting() & $errno) {
                throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
            }
        });
        try {
            $data = json_decode(file_get_contents($url), true);
            if (!$data) {
                throw new Exception('Failed to retrieve data from ' . $url);
            }

            return $this->handleResponse($data);
        } catch (\ErrorException $e) {
            throw $e;
        } finally {
            restore_error_handler();
        }
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