<?php

namespace SlmQueueSqs\Command;

use Symfony\Component\Console\Input\InputArgument;

class StartWorkerCommand extends \SlmQueue\Command\StartWorkerCommand {
    protected function configure(): void
    {
        parent::configure();

        $this->addArgument('visibilityTimeout', InputArgument::OPTIONAL);
        $this->addArgument('waitTime', InputArgument::OPTIONAL);
    }
}