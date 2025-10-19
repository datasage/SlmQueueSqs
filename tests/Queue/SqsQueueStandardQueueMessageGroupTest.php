<?php

namespace SlmQueueSqsTest\Queue;

use PHPUnit\Framework\TestCase;
use SlmQueueSqs\Options\SqsQueueOptions;
use SlmQueueSqs\Queue\SqsQueue;
use SlmQueueSqsTest\Asset;
use Aws\Sqs\SqsClient;
use SlmQueue\Job\JobPluginManager;

class SqsQueueStandardQueueMessageGroupTest extends TestCase
{
    private SqsClient $sqsClient;
    private SqsQueue $sqsQueue;
    private JobPluginManager $jobPluginManager;

    protected function setUp(): void
    {
        $this->sqsClient = $this->getMockBuilder(SqsClient::class)
            ->addMethods(['getQueueUrl', 'sendMessage', 'sendMessageBatch'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->jobPluginManager = $this->getMockBuilder(JobPluginManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $options = new SqsQueueOptions(['queue_url' => 'https://sqs.endpoint.com']);
        $this->sqsQueue = new SqsQueue($this->sqsClient, $options, 'newsletter', $this->jobPluginManager);
    }

    public function testMessageGroupIdIsPassedForStandardQueueWhenProvided()
    {
        $job = new Asset\SimpleJob();

        $options = ['message_group_id' => 'group-1'];

        $result = [
            'MessageId' => 1,
            'MD5OfMessageBody' => md5('baz')
        ];

        $this->sqsClient
            ->expects($this->once())
            ->method('sendMessage')
            ->with([
                'QueueUrl' => 'https://sqs.endpoint.com',
                'MessageBody' => $this->sqsQueue->serializeJob($job),
                'MessageGroupId' => 'group-1',
            ])
            ->willReturn($result);

        $this->sqsQueue->push($job, $options);
    }

    public function testMessageGroupIdIsPassedForStandardQueueInBatchWhenProvided()
    {
        $jobs = [new Asset\SimpleJob(), new Asset\SimpleJob()];
        $options = [
            0 => ['message_group_id' => 'g0'],
            1 => ['message_group_id' => 'g1'],
        ];

        $this->sqsClient
            ->expects($this->once())
            ->method('sendMessageBatch')
            ->with([
                'QueueUrl' => 'https://sqs.endpoint.com',
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
