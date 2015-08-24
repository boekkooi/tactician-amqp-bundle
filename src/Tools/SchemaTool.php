<?php
namespace Boekkooi\Bundle\AMQP\Tools;

use Boekkooi\Bundle\AMQP\LazyConnection;
use Boekkooi\Bundle\AMQP\LazyExchange;
use Boekkooi\Bundle\AMQP\LazyQueue;

class SchemaTool
{
    /**
     * @var LazyConnection
     */
    private $connection;

    public function __construct(LazyConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param LazyExchange[] $exchanges
     * @param LazyQueue[] $queues
     */
    public function declareDefinitions(array $exchanges, array $queues)
    {
        $this->exec(function (\AMQPChannel $channel) use ($exchanges, $queues) {
            foreach ($exchanges as $exchange) {
                $exchange->create($channel, true);
            }

            foreach ($queues as $queue) {
                $queue->create($channel, true);
            }
        });
    }

    /**
     * @param LazyQueue[] $queues
     */
    public function purgeQueues(array $queues)
    {
        $this->exec(function (\AMQPChannel $channel) use ($queues) {
            foreach ($queues as $queue) {
                $queue
                    ->create($channel)
                    ->purge();
            }
        });
    }

    private function exec(callable $callback)
    {
        $connection = $this->connection->instance();
        $callback(new \AMQPChannel($connection));
    }
}
