<?php

use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;
use SlmQueueSqs\Command\StartWorkerCommand;

return array(
    'service_manager' => array(
        'factories' => array(
            StartWorkerCommand::class => ReflectionBasedAbstractFactory::class,
            \Aws\Sdk::class => \SlmQueueSqs\Factory\AwsFactory::class,
            \SlmQueueSqs\Worker\SqsWorker::class => \SlmQueue\Factory\WorkerAbstractFactory::class,
            \SlmQueueSqs\Queue\SqsQueue::class => \SlmQueueSqs\Factory\SqsQueueFactory::class
        )
    ),

    'laminas-cli' => [
        'commands' => [
            'slm-queue:sqs' => StartWorkerCommand::class,
        ],
    ],
);
