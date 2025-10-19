<?php

namespace SlmQueueSqsTest\Factory;

use Aws\Result;
use Aws\Sdk as AwsSdk;
use GuzzleHttp\Promise\Create as PromiseCreate;
use Psr\Container\ContainerInterface;
use Aws\CommandInterface;
use Psr\Http\Message\RequestInterface;

class TestAwsFactory
{
    public function __invoke(ContainerInterface $container): AwsSdk
    {
        // In-memory messages store to simulate a single SQS queue
        $messages = [];
        $receiptCounter = 0;
        $messageIdCounter = 0;

        // Provide a perpetual base handler callable used by the SDK
        $baseHandler = function (CommandInterface $command, RequestInterface $request) use (&$messages, &$receiptCounter, &$messageIdCounter) {
            $name = $command->getName();

            switch ($name) {
                case 'SendMessage':
                    $body = $command['MessageBody'] ?? '';
                    $messageIdCounter++;
                    $receiptCounter++;

                    $receipt = 'rh-' . $receiptCounter;
                    $msgId   = (string) $messageIdCounter;

                    // Store message as SQS would
                    $messages[] = [
                        'MessageId'     => $msgId,
                        'ReceiptHandle' => $receipt,
                        'Body'          => $body,
                    ];

                    return PromiseCreate::promiseFor(new Result([
                        'MessageId' => $msgId,
                        'MD5OfMessageBody' => md5($body),
                    ]));

                case 'ReceiveMessage':
                    if (!empty($messages)) {
                        // Return the first message but do not remove it until DeleteMessage
                        $message = $messages[0];
                        return PromiseCreate::promiseFor(new Result([
                            'Messages' => [
                                [
                                    'MessageId'     => $message['MessageId'],
                                    'ReceiptHandle' => $message['ReceiptHandle'],
                                    'Body'          => $message['Body'],
                                ]
                            ]
                        ]));
                    }

                    return PromiseCreate::promiseFor(new Result([]));

                case 'DeleteMessage':
                    $receipt = $command['ReceiptHandle'] ?? null;
                    if ($receipt) {
                        // Remove the first matching message
                        foreach ($messages as $i => $m) {
                            if ($m['ReceiptHandle'] === $receipt) {
                                array_splice($messages, $i, 1);
                                break;
                            }
                        }
                    }
                    return PromiseCreate::promiseFor(new Result([]));

                // Fallback for any other SQS operations used by tests
                default:
                    return PromiseCreate::promiseFor(new Result([]));
            }
        };

        $config = [
            'region'  => 'eu-west-1',
            'version' => 'latest',
            'handler' => $baseHandler,
        ];

        return new AwsSdk($config);
    }
}
