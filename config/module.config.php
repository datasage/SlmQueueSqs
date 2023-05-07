<?php

use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;
use SlmQueueSqs\Command\StartWorkerCommand;

return array(
    'service_manager' => array(
        'factories' => array(
            StartWorkerCommand::class => ReflectionBasedAbstractFactory::class,
            'SlmQueueSqs\Worker\SqsWorker' => 'SlmQueue\Factory\WorkerFactory'
        )
    ),

    'laminas-cli' => [
        'commands' => [
            'slm-queue:sqs' => StartWorkerCommand::class,
        ],
    ],
);
