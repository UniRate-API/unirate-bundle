<?php

declare(strict_types=1);

namespace UniRateApi\Bundle;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class UniRateClient
{
    public const VERSION = '0.1.0';

    private readonly string $baseUrl;

    public function __construct(
        private readonly string $apiKey,
        private readonly HttpClientInterface $httpClient,
        string $baseUrl = 'https://api.unirateapi.com',
        private readonly int $timeout = 30,
    ) {
        if ($apiKey === '') {
            throw new UniRateException('UniRateClient: apiKey must not be empty.');
        }
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Get the exchange rate for a currency pair.
     *
     * @return float When $to is specified.
     */
    public function getRate(string $from, string $to): float
    {
        $data = $this->request('/api/rates', [
            'from' => strtoupper($from),
            'to'   => strtoupper($to),
        ]);

        if (!isset($data['rate'])) {
            throw new UniRateException('Malformed /api/rates response: missing "rate" key.');
        }

        return (float) $data['rate'];
    }

    /**
     * Get all exchange rates for a base currency.
     *
     * @return array<string, float>
     */
    public function getRates(string $base = 'USD'): array
    {
        $data = $this->request('/api/rates', ['from' => strtoupper($base)]);

        $out = [];
        foreach (($data['rates'] ?? []) as $code => $value) {
            $out[(string) $code] = (float) $value;
        }

        return $out;
    }

    /**
     * Convert an amount from one currency to another.
     */
    public function convert(float $amount, string $from, string $to): float
    {
        $data = $this->request('/api/convert', [
            'from'   => strtoupper($from),
            'to'     => strtoupper($to),
            'amount' => $amount,
        ]);

        if (!isset($data['result'])) {
            throw new UniRateException('Malformed /api/convert response: missing "result" key.');
        }

        return (float) $data['result'];
    }

    /**
     * List all supported currency codes.
     *
     * @return list<string>
     */
    public function listCurrencies(): array
    {
        $data = $this->request('/api/currencies', []);
        return array_values(array_map('strval', $data['currencies'] ?? []));
    }

    /**
     * Get VAT rates for all countries or a specific country.
     *
     * @return array<string, mixed>
     */
    public function getVatRates(?string $country = null): array
    {
        $params = [];
        if ($country !== null) {
            $params['country'] = strtoupper($country);
        }
        return $this->request('/api/vat/rates', $params);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    private function request(string $path, array $query): array
    {
        $query['api_key'] = $this->apiKey;

        $response = $this->httpClient->request('GET', $this->baseUrl . $path, [
            'query'   => $query,
            'timeout' => $this->timeout,
            'headers' => [
                'Accept'     => 'application/json',
                'User-Agent' => 'unirate-bundle/' . self::VERSION,
            ],
        ]);

        $status = $response->getStatusCode();

        if ($status >= 400) {
            $body = substr($response->getContent(false), 0, 300);
            throw match (true) {
                $status === 401 => new UniRateException('Missing or invalid API key.', $status),
                $status === 403 => new UniRateException('Endpoint requires a Pro subscription.', $status),
                $status === 404 => new UniRateException('Currency not found or endpoint unavailable.', $status),
                $status === 429 => new UniRateException('Rate limit exceeded.', $status),
                default         => new UniRateException("API error (HTTP {$status}): {$body}", $status),
            };
        }

        $data = $response->toArray();

        if (!is_array($data)) {
            throw new UniRateException("Expected JSON object from {$path}.");
        }

        return $data;
    }
}
