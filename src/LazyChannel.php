<?php
namespace Boekkooi\Bundle\AMQP;

/**
 * A class used to initialize a @see \AMQPChannel channel
 * @final
 */
class LazyChannel
{
    public function create(\AMQPConnection $connection)
    {
        return new \AMQPChannel($connection);
    }
}
