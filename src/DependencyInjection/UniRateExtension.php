<?php

declare(strict_types=1);

namespace UniRateApi\Bundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use UniRateApi\Bundle\UniRateClient;

class UniRateExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('unirate.api_key', $config['api_key']);
        $container->setParameter('unirate.base_url', $config['base_url']);
        $container->setParameter('unirate.timeout', $config['timeout']);

        $definition = $container->autowire(UniRateClient::class);
        $definition->setArgument('$apiKey', $config['api_key']);
        $definition->setArgument('$baseUrl', $config['base_url']);
        $definition->setArgument('$timeout', $config['timeout']);
        $definition->setPublic(true);
    }
}
