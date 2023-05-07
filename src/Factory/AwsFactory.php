<?php

namespace SlmQueueSqs\Factory;

use Aws\Sdk as AwsSdk;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Factory used to instantiate an AWS client
 */
class AwsFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return AwsSdk
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // Instantiate the AWS SDK for PHP
        $config = $container->get('Config');
        $config = $config['aws'] ?? [];

        return new AwsSdk($config);
    }
}