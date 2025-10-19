<?php

namespace SlmQueueSqsTest\Command;

use Aws\MockHandler;
use Aws\Result;
use Aws\Sdk;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use PHPUnit\Framework\TestCase;
use SlmQueueSqs\Command\StartWorkerCommand;
use SlmQueue\Queue\QueuePluginManager;
use SlmQueue\Worker\WorkerPluginManager;
use SlmQueueSqsTest\Asset\FailingJob;
use SlmQueueSqsTest\Asset\SimpleJob;
use SlmQueueSqsTest\Asset\SimpleWorker;
use SlmQueueSqsTest\Util\ServiceManagerFactory;
use Symfony\Component\Console\Exception\RuntimeException as ConsoleRuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class StartWorkerCommandTest extends TestCase
{
    private QueuePluginManager $queuePluginManager;
    private WorkerPluginManager $workerPluginManager;
    private CommandTester $command;

    private MockHandler $mockHandler;

    public function setUp(): void
    {
        ServiceManagerFactory::setConfig(include __DIR__ . '/../TestConfiguration.php.dist');
        $serviceManager = ServiceManagerFactory::getServiceManager();

        $this->mockHandler = new MockHandler();

        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(
            Sdk::class,
            new Sdk([
                'region' => 'us-west-1',
                'credentials' => [ 'key' => 'my-key', 'secret' => 'my-secret' ],
                'handler' => $this->mockHandler
            ])
        );
        $serviceManager->setAllowOverride(false);

        $this->queuePluginManager = $serviceManager->get(QueuePluginManager::class);
        $this->workerPluginManager = $serviceManager->get(WorkerPluginManager::class);

        $queue = $this->queuePluginManager->get('newsletter');

        /** @var SimpleWorker */
        $worker = $this->workerPluginManager->get($queue->getWorkerName());
        $eventManager = $worker->getEventManager();

        $this->command = new CommandTester(
            new StartWorkerCommand($this->queuePluginManager, $this->workerPluginManager)
        );
    }

    public function testThrowExceptionIfQueueIsUnknown(): void
    {
        $this->expectException(ServiceNotFoundException::class);

        $this->command->execute([
            'queue' => 'unknown',
        ]);
    }

    public function testThrowExceptionIfNoQueue(): void
    {
        $this->expectException(ConsoleRuntimeException::class);

        $this->command->execute([]);
    }

    public function testSimpleJob(): void
    {
        $queue = $this->queuePluginManager->get('newsletter');

        $this->mockHandler->append(new Result([
            'MD5OfMessageBody' => '5907f15bb9106cddfe703dbcfe59d747',
            'MessageId' => '1234567890',
        ]));

        for ($i = 0; $i < 25; $i++) {
            $this->mockHandler->append(new Result([
                'Messages' => [
                    [
                        'Body' => $queue->serializeJob(new SimpleJob()),
                        'ReceiptHandle' => '1234567890',
                        'MD5OfBody' => 'cc80812449e6a7332c2d0cbf97fd583e',
                        'MessageId' => '1234567890',
                    ]
                ]
            ]));
        }

        $queue->push(new SimpleJob());

        $this->command->execute([
            'queue' => 'newsletter',
        ]);

        $this->command->assertCommandIsSuccessful();
        $this->assertStringContainsString("Finished worker for queue 'newsletter'", $this->command->getDisplay());
        $this->assertStringContainsString("maximum of 1 jobs processed", $this->command->getDisplay());
    }

    public function testFailingJobThrowException(): void
    {
        $queue = $this->queuePluginManager->get('newsletter');

        $this->mockHandler->append(new Result([
            'MD5OfMessageBody' => '5907f15bb9106cddfe703dbcfe59d747',
            'MessageId' => '1234567890',
        ]));

        for ($i = 0; $i < 25; $i++) {
            $this->mockHandler->append(new Result([
                'Messages' => [
                    [
                        'Body' => $queue->serializeJob(new FailingJob()),
                        'ReceiptHandle' => '1234567890',
                        'MD5OfBody' => '5907f15bb9106cddfe703dbcfe59d747',
                        'MessageId' => '1234567890',
                    ]
                ]
            ]));
        }

        $queue->push(new FailingJob());

        $this->command->execute([
            'queue' => 'newsletter',
        ]);

        $this->command->assertCommandIsSuccessful();

        $this->assertStringContainsString("Finished worker for queue 'newsletter'", $this->command->getDisplay());
        $this->assertStringContainsString("maximum of 1 jobs processed", $this->command->getDisplay());
    }
}
