<?php

namespace SlmQueueSqsTest\Queue;

use PHPUnit\Framework\TestCase;
use SlmQueueSqs\Options\SqsQueueOptions;
use SlmQueueSqs\Queue\SqsQueue;
use SlmQueueSqsTest\Asset;
use Aws\Sqs\SqsClient;
use SlmQueue\Job\JobPluginManager;

class SqsQueueFifoDelayTest extends TestCase
{
    private SqsClient $sqsClient;
    private SqsQueue $sqsQueue;
    private JobPluginManager $jobPluginManager;

    protected function setUp(): void
    {
        $this->sqsClient = $this->getMockBuilder(SqsClient::class)
            ->addMethods(['sendMessage', 'sendMessageBatch'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->jobPluginManager = $this->getMockBuilder(JobPluginManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $options = new SqsQueueOptions(['queue_url' => 'https://sqs.endpoint.com/test.fifo']);
        $this->sqsQueue = new SqsQueue($this->sqsClient, $options, 'newsletter', $this->jobPluginManager);
    }

    public function testDelaySecondsIsNotSentForFifoQueueOnPush()
    {
        $job = new Asset\SimpleJob();

        $result = [
            'MessageId' => 1,
            'MD5OfMessageBody' => md5('baz')
        ];

        $this->sqsClient
            ->expects($this->once())
            ->method('sendMessage')
            ->with([
                'QueueUrl' => 'https://sqs.endpoint.com/test.fifo',
                'MessageBody' => $this->sqsQueue->serializeJob($job),
                // No DelaySeconds should be present for FIFO queues
                'MessageGroupId' => 'group-1',
            ])
            ->willReturn($result);

        $this->sqsQueue->push($job, [
            'delay_seconds' => 10,
            'message_group_id' => 'group-1',
        ]);
    }

    public function testDelaySecondsIsNotSentForFifoQueueOnBatchPush()
    {
        $jobs = [new Asset\SimpleJob(), new Asset\SimpleJob()];
        $options = [
            0 => ['delay_seconds' => 5, 'message_group_id' => 'g0'],
            1 => ['delay_seconds' => 15, 'message_group_id' => 'g1'],
        ];

        $this->sqsClient
            ->expects($this->once())
            ->method('sendMessageBatch')
            ->with([
                'QueueUrl' => 'https://sqs.endpoint.com/test.fifo',
                'Entries' => [
                    [
                        'Id' => 0,
                        'MessageBody' => $this->sqsQueue->serializeJob($jobs[0]),
                        'MessageGroupId' => 'g0',
                    ],
                    [
                        'Id' => 1,
                        'MessageBody' => $this->sqsQueue->serializeJob($jobs[1]),
                        'MessageGroupId' => 'g1',
                    ],
                ],
            ])
            ->willReturn([
                'Successful' => [
                    ['Id' => 0, 'MessageId' => 1, 'MD5OfMessageBody' => md5('bar')],
                    ['Id' => 1, 'MessageId' => 2, 'MD5OfMessageBody' => md5('baz')],
                ],
            ]);

        $this->sqsQueue->batchPush($jobs, $options);
    }
}
