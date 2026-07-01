<?php

declare(strict_types=1);

namespace UniRateApi\Bundle\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use UniRateApi\Bundle\UniRateClient;
use UniRateApi\Bundle\UniRateException;

class UniRateClientTest extends TestCase
{
    private function makeResponse(array $data, int $status = 200): ResponseInterface&MockObject
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($status);
        $response->method('toArray')->willReturn($data);
        $response->method('getContent')->willReturn(json_encode($data) ?: '');
        return $response;
    }

    private function makeClient(array $responseData, int $status = 200): UniRateClient
    {
        $response = $this->makeResponse($responseData, $status);
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('request')->willReturn($response);
        return new UniRateClient(apiKey: 'test-key', httpClient: $http);
    }

    // Constructor tests

    public function testThrowsWhenApiKeyIsEmpty(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $this->expectException(UniRateException::class);
        new UniRateClient(apiKey: '', httpClient: $http);
    }

    public function testConstructsWithValidKey(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $client = new UniRateClient(apiKey: 'key', httpClient: $http);
        $this->assertInstanceOf(UniRateClient::class, $client);
    }

    // getRate tests

    public function testGetRateReturnsParsedFloat(): void
    {
        $client = $this->makeClient(['rate' => '0.92']);
        $this->assertEqualsWithDelta(0.92, $client->getRate('USD', 'EUR'), 0.0001);
    }

    public function testGetRateThrowsOnMissingKey(): void
    {
        $client = $this->makeClient([]);
        $this->expectException(UniRateException::class);
        $client->getRate('USD', 'EUR');
    }

    public function testGetRateUppercasesCurrencies(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $response = $this->makeResponse(['rate' => '0.92']);
        $http->expects($this->once())->method('request')->with(
            'GET',
            $this->stringContains('/api/rates'),
            $this->callback(function ($opts) {
                return isset($opts['query']['from']) && $opts['query']['from'] === 'USD'
                    && isset($opts['query']['to']) && $opts['query']['to'] === 'EUR';
            })
        )->willReturn($response);
        $client = new UniRateClient(apiKey: 'k', httpClient: $http);
        $client->getRate('usd', 'eur');
    }

    // getRates tests

    public function testGetRatesReturnsMap(): void
    {
        $client = $this->makeClient(['rates' => ['EUR' => '0.92', 'GBP' => '0.79']]);
        $rates = $client->getRates('USD');
        $this->assertEqualsWithDelta(0.92, $rates['EUR'], 0.0001);
        $this->assertEqualsWithDelta(0.79, $rates['GBP'], 0.0001);
    }

    public function testGetRatesReturnsEmptyArrayWhenMissingKey(): void
    {
        $client = $this->makeClient([]);
        $rates = $client->getRates('USD');
        $this->assertSame([], $rates);
    }

    // convert tests

    public function testConvertReturnsFloat(): void
    {
        $client = $this->makeClient(['result' => '92.5']);
        $this->assertEqualsWithDelta(92.5, $client->convert(100.0, 'USD', 'EUR'), 0.0001);
    }

    public function testConvertThrowsOnMissingResult(): void
    {
        $client = $this->makeClient([]);
        $this->expectException(UniRateException::class);
        $client->convert(100, 'USD', 'EUR');
    }

    // listCurrencies tests

    public function testListCurrenciesReturnsArray(): void
    {
        $client = $this->makeClient(['currencies' => ['USD', 'EUR', 'GBP']]);
        $this->assertSame(['USD', 'EUR', 'GBP'], $client->listCurrencies());
    }

    public function testListCurrenciesReturnsEmptyOnMissingKey(): void
    {
        $client = $this->makeClient([]);
        $this->assertSame([], $client->listCurrencies());
    }

    // getVatRates tests

    public function testGetVatRatesWithoutCountry(): void
    {
        $vatData = ['total_countries' => 50, 'vat_rates' => ['DE' => ['vat_rate' => 19.0]]];
        $client = $this->makeClient($vatData);
        $this->assertSame($vatData, $client->getVatRates());
    }

    public function testGetVatRatesWithCountryUppercased(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $response = $this->makeResponse(['country' => 'DE']);
        $http->expects($this->once())->method('request')->with(
            'GET',
            $this->anything(),
            $this->callback(fn($opts) => ($opts['query']['country'] ?? null) === 'DE')
        )->willReturn($response);
        $client = new UniRateClient(apiKey: 'k', httpClient: $http);
        $client->getVatRates('de');
    }

    // Error mapping tests

    public static function errorStatusProvider(): array
    {
        return [
            [401, 'invalid API key'],
            [403, 'Pro subscription'],
            [404, 'Currency not found'],
            [429, 'Rate limit'],
            [500, 'API error'],
        ];
    }

    #[DataProvider('errorStatusProvider')]
    public function testHttpErrorMappedToUniRateException(int $status, string $fragment): void
    {
        $response = $this->makeResponse([], $status);
        $response->method('getContent')->willReturn('{"error":"' . $fragment . '"}');
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('request')->willReturn($response);
        $client = new UniRateClient(apiKey: 'k', httpClient: $http);

        $this->expectException(UniRateException::class);
        $client->getRate('USD', 'EUR');
    }

    public function testExceptionIncludesStatusCode(): void
    {
        $client = $this->makeClient(['error' => 'bad key'], 401);
        try {
            $client->getRates('USD');
            $this->fail('Expected UniRateException');
        } catch (UniRateException $e) {
            $this->assertSame(401, $e->getStatusCode());
        }
    }

    public function testApiKeyIsNotIncludedInUserAgent(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $response = $this->makeResponse(['rate' => '0.92']);
        $http->method('request')->with(
            'GET',
            $this->anything(),
            $this->callback(function ($opts) {
                $ua = $opts['headers']['User-Agent'] ?? '';
                return str_contains($ua, 'unirate-bundle/') && !str_contains($ua, 'secret');
            })
        )->willReturn($response);
        $client = new UniRateClient(apiKey: 'secret', httpClient: $http);
        $client->getRate('USD', 'EUR');
    }

    public function testAcceptHeaderIsJson(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $response = $this->makeResponse(['rate' => '0.92']);
        $http->method('request')->with(
            'GET',
            $this->anything(),
            $this->callback(fn($opts) => ($opts['headers']['Accept'] ?? '') === 'application/json')
        )->willReturn($response);
        $client = new UniRateClient(apiKey: 'k', httpClient: $http);
        $client->getRate('USD', 'EUR');
    }
}
