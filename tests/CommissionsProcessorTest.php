<?php
declare(strict_types=1);

namespace Refactoring\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Refactoring\CommissionsProcessor;
use Refactoring\Exceptions\HttpRequestException;
use Refactoring\Providers\BINInfo\BINInfoProvider;
use Refactoring\Providers\CurrencyRates\CurrencyRatesProvider;

class CommissionsProcessorTest extends TestCase
{
    private $currencyRatesProviderMock;
    private $binInfoProviderMock;

    protected function setUp(): void
    {
        $this->currencyRatesProviderMock = $this->createMock(CurrencyRatesProvider::class);
        $this->binInfoProviderMock = $this->createMock(BINInfoProvider::class);
    }

    public function testCalculate(): void
    {
        $this->currencyRatesProviderMock
            ->expects(self::once())
            ->method('fetch')
            ->willReturn(['rates' => ['EUR' => 1, 'USD' => 1.105987, 'JPY' => 158.446529, 'GBP' => 0.844493]]);

        $this->binInfoProviderMock
            ->expects(self::exactly(7))
            ->method('fetch')
            ->willReturnCallback(function ($bin) {
                return match ($bin) {
                    '45717360' => ['country' => ['alpha2' => 'DK']],
                    '516793', '4745030' => ['country' => ['alpha2' => 'LT']],
                    '45417360' => ['country' => ['alpha2' => 'JP']],
                    '41417360' => ['country' => []],
                    '1111' => throw new HttpRequestException('Too Many Requests'),
                    default => throw new \JsonException('Invalid data'),
                };
            });

        $processorMock = $this->getMockBuilder(CommissionsProcessor::class)
            ->onlyMethods(['getTransactions'])
            ->setConstructorArgs([$this->currencyRatesProviderMock, $this->binInfoProviderMock])
            ->getMock();;

        $processorMock->method('getTransactions')
            ->with('input_file')
            ->willReturn($this->createGenerator([
                ['bin' => '45717360', 'amount' => '100.00', 'currency' => 'EUR'],
                ['bin' => '516793', 'amount' => '50.00', 'currency' => 'USD'],
                ['bin' => '45417360', 'amount' => '10000.00', 'currency' => 'JPY'],
                ['bin' => '41417360', 'amount' => '130.00', 'currency' => 'USD'],
                ['bin' => '4745030', 'amount' => '2000.00', 'currency' => 'GBP'],
                ['bin' => '1111', 'amount' => '1.00', 'currency' => 'EUR'],
                ['bin' => '9999', 'amount' => '1.00', 'currency' => 'EUR'],
            ]));

        $processorMock->calculate('input_file');

        $this->expectOutputString(
            "1\n0.46\n1.27\n2.36\n23.69\n" . //success
            'LOG: Calculation failed. Reason: Too Many Requests. Transaction data: {"bin":"1111","amount":"1.00","currency":"EUR"}' . "\n" .
            'LOG: Calculation failed. Reason: Invalid data. Transaction data: {"bin":"9999","amount":"1.00","currency":"EUR"}' . "\n"
        );
    }

    private function createGenerator(array $data): \Generator
    {
        foreach ($data as $item) {
            yield $item;
        }
    }

    public function testCalculateFileDoesNotExist(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File does not exist or cannot be read: non_existent_file.txt');

        $processor = new CommissionsProcessor($this->currencyRatesProviderMock, $this->binInfoProviderMock);
        $processor->calculate('non_existent_file.txt');
    }

    public static function getTestCases(): array
    {
        return [
            [
                '{"bin":"45717360","amount":"100.00","currency":"EUR"}',
                ['bin' => '45717360', 'amount' => '100.00', 'currency' => 'EUR'],
            ],
            [
                '{"bin":"45717360","amount":"100.00"}',
                [],
                'LOG: Missing required keys in JSON: {"bin":"45717360","amount":"100.00"}'
            ],
            [
                '{"bin":45717360',
                [],
                'LOG: Invalid JSON: {"bin":45717360'
            ],
        ];
    }

    #[DataProvider('getTestCases')]
    public function testExtractData(string $json, array $expected, string $message = ''): void
    {
        $processor = new class(
            $this->currencyRatesProviderMock,
            $this->binInfoProviderMock
        ) extends CommissionsProcessor {
            public function publicExtractData(string $json): array
            {
                return $this->extractData($json);
            }
        };

        if (!empty($message)) {
            $this->expectOutputString($message."\n");
        }
        $this->assertEquals($expected, $processor->publicExtractData($json));
    }

}