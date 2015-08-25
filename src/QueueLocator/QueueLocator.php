<?php
namespace Boekkooi\Bundle\AMQP\QueueLocator;

use Boekkooi\Bundle\AMQP\LazyQueue;

interface QueueLocator
{
    /**
     * Retrieves the queue by it's name.
     *
     * @param string $queue
     * @return LazyQueue
     */
    public function getQueueByName($queue);
}
