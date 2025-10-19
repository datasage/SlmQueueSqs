<?php

namespace SlmQueueSqs\Options;

use Laminas\Stdlib\AbstractOptions;

/**
 * Simple queue options
 */
class SqsQueueOptions extends AbstractOptions
{
    protected ?string $queueUrl = null;

    /**
     * Set the queue URL
     */
    public function setQueueUrl(string $queueUrl)
    {
        $this->queueUrl = $queueUrl;
    }

    /**
     * Get the queue URL
     */
    public function getQueueUrl(): ?string
    {
        return $this->queueUrl;
    }
}
