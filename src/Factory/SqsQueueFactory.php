<?php

namespace SlmQueueSqs\Factory;

use Aws\Sdk;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use SlmQueue\Job\JobPluginManager;
use SlmQueueSqs\Options\SqsQueueOptions;
use SlmQueueSqs\Queue\SqsQueue;

/**
 * SqsQueueFactory
 */
class SqsQueueFactory implements FactoryInterface
{
    /**
     * @param  ContainerInterface $container
     * @param  string             $requestedName
     * @param  array|null         $options
     * @return SqsQueue
     */
    #[\Override]
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): SqsQueue
    {
        $sqsClient        = $container->get(Sdk::class)->createSqs();
        $jobPluginManager = $container->get(JobPluginManager::class);

        // Let's see if we have options for this specific queue
        $config = $container->get('Config');
        $config = $config['slm_queue']['queues'];

        $queueOptions = new SqsQueueOptions(isset($config[$requestedName]) ? $config[$requestedName] : []);

        return new SqsQueue($sqsClient, $queueOptions, $requestedName, $jobPluginManager);
    }
}
