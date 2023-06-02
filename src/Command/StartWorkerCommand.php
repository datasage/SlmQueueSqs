<?php

namespace SlmQueueSqs\Command;

use SlmQueue\Controller\Exception\WorkerProcessException;
use SlmQueue\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StartWorkerCommand extends \SlmQueue\Command\StartWorkerCommand {
    protected function configure(): void
    {
        parent::configure();

        $this->addOption('visibilityTimeout',  null, InputArgument::OPTIONAL);
        $this->addOption('waitTime', null, InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queueName = $input->getArgument('queue');
        $queue = $this->queuePluginManager->get($queueName);
        $worker = $this->workerPluginManager->get($queue->getWorkerName());

        try {
            $messages = $worker->processQueue($queue, $input->getArguments() + $input->getOptions());
        } catch (ExceptionInterface $e) {
            throw new WorkerProcessException(
                'Caught exception while processing queue',
                $e->getCode(),
                $e
            );
        }

        $messages = implode("\n", array_map(function (string $message): string {
            return sprintf(' - %s', $message);
        }, $messages));

        $output->writeln(sprintf(
            "Finished worker for queue '%s':\n%s\n",
            $queueName,
            $messages
        ));

        return 0;
    }
}