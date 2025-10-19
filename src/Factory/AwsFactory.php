<?php

namespace SlmQueueSqs\Factory;

use Aws\Sdk as AwsSdk;
use Psr\Container\ContainerInterface;

class AwsFactory
{
    public function __invoke(ContainerInterface $container): AwsSdk
    {
        // Instantiate the AWS SDK for PHP
        $config = $container->get('Config');
        $config = $config['aws'] ?? [];

        return new AwsSdk($config);
    }
}
