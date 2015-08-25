<?php
namespace Boekkooi\Bundle\AMQP\Consumer;

use Boekkooi\Bundle\AMQP\LazyChannel;
use Boekkooi\Bundle\AMQP\LazyConnection;
use Boekkooi\Bundle\AMQP\QueueLocator\QueueLocator;

class Consumer
{
    /**
     * @var LazyConnection
     */
    private $connection;

    /**
     * @var LazyChannel
     */
    private $channel;

    /**
     * @var QueueLocator
     */
    private $queueLocator;

    public function __construct(LazyConnection $connection, LazyChannel $channel, QueueLocator $queueLocator)
    {
        $this->connection = $connection;
        $this->channel = $channel;
        $this->queueLocator = $queueLocator;
    }

    public function consume(array $queueNames, callable $callback)
    {
        // Open AMQP connection
        $connection = $this->connection->create();

        try {
            // Initialize channel and queues
            $channel = $this->channel->create($connection);
            $queues = $this->queuesInitialize($channel, $queueNames);

            // Consume and cancel once done
            $cancel = true;
            try {
                $this->queuesConsume($queues, $callback);
            } catch (\AMQPQueueException $e) {
                $cancel = false;
                // A timeout is not a true exception in your case
                // So don't throw it
                if ($e->getMessage() !== 'Consumer timeout exceed') {
                    throw $e;
                }
            } catch (\AMQPException $e) {
                $cancel = false;
                throw $e;
            } finally {
                $this->destroyQueues($queues, $cancel);
            }
        } finally {
            // Close connection when done
            if ($connection->isConnected()) {
                $connection->disconnect();
            }
            unset($channel, $connection);
        }
    }

    /**
     * @param \AMQPChannel $channel
     * @param string[] $queueNames
     * @return \AMQPQueue[]
     */
    private function queuesInitialize(\AMQPChannel $channel, array $queueNames)
    {
        $queues = [];
        foreach ($queueNames as $queueName) {
            $queues[] = $this->queueLocator
                ->getQueueByName($queueName)
                ->create($channel);
        }
        return $queues;
    }

    /**
     * @param callable $callback
     * @param \AMQPQueue[] $queues
     */
    private function queuesConsume(array $queues, callable $callback)
    {
        /** @var \AMQPQueue $consumeQueue */
        $consumeQueue = array_pop($queues);

        # In case of multiple queues we bind without any arguments
        # This allows multiple to be consumed by one callback
        foreach ($queues as $queue) {
            $queue->consume(null);
        }

        $consumeQueue->consume($callback);
    }

    /**
     * @param \AMQPQueue[] $queues
     * @param bool $cancel
     */
    private function destroyQueues(&$queues, $cancel)
    {
        # Cancel consumption of the queue
        while (!empty($queues)) {
            $queue = array_pop($queues);
            if ($cancel) {
                $queue->cancel();
            }
            unset($queue);
        }
    }
}
