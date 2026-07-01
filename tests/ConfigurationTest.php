<?php

declare(strict_types=1);

namespace UniRateApi\Bundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use UniRateApi\Bundle\DependencyInjection\Configuration;

class ConfigurationTest extends TestCase
{
    private function process(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), [$config]);
    }

    public function testApiKeyIsRequired(): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);
        $this->process([]);
    }

    public function testApiKeyCannotBeEmpty(): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);
        $this->process(['api_key' => '']);
    }

    public function testDefaultBaseUrl(): void
    {
        $config = $this->process(['api_key' => 'test-key']);
        $this->assertSame('https://api.unirateapi.com', $config['base_url']);
    }

    public function testDefaultTimeout(): void
    {
        $config = $this->process(['api_key' => 'test-key']);
        $this->assertSame(30, $config['timeout']);
    }

    public function testCustomBaseUrl(): void
    {
        $config = $this->process(['api_key' => 'k', 'base_url' => 'http://localhost:9999']);
        $this->assertSame('http://localhost:9999', $config['base_url']);
    }

    public function testCustomTimeout(): void
    {
        $config = $this->process(['api_key' => 'k', 'timeout' => 60]);
        $this->assertSame(60, $config['timeout']);
    }

    public function testAllDefaults(): void
    {
        $config = $this->process(['api_key' => 'my-key']);
        $this->assertSame('my-key', $config['api_key']);
        $this->assertArrayHasKey('base_url', $config);
        $this->assertArrayHasKey('timeout', $config);
    }
}
