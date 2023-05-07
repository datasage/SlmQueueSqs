<?php

namespace SlmQueueSqs\Command;

use Symfony\Component\Console\Input\InputArgument;

class StartWorkerCommand extends \SlmQueue\Command\StartWorkerCommand {
    protected function configure(): void
    {
        parent::configure();

        $this->addOption('visibilityTimeout',  null, InputArgument::OPTIONAL);
        $this->addOption('waitTime', null, InputArgument::OPTIONAL);
    }
}