<?php
namespace Boekkooi\Bundle\AMQP;

class LazyQueue
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var int
     */
    private $flags;
    /**
     * @var array
     */
    private $arguments;
    /**
     * @var array
     */
    private $binds;

    public function __construct($name, $passive = false, $durable = true, $exclusive = false, $auto_delete = false, array $arguments = [], array $binds = [])
    {
        $this->name = $name;
        $this->binds = $binds;
        $this->flags = (
            ($passive ? AMQP_PASSIVE : AMQP_NOPARAM) |
            ($durable ? AMQP_DURABLE : AMQP_NOPARAM) |
            ($exclusive ? AMQP_EXCLUSIVE : AMQP_NOPARAM) |
            ($auto_delete ? AMQP_AUTODELETE : AMQP_NOPARAM)
        );
        $this->arguments = $arguments;
    }

    /**
     * Get the name of the queue
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Indicates if the queue is durable
     *
     * @return bool
     */
    public function isDurable()
    {
        return ($this->flags & AMQP_DURABLE) === AMQP_DURABLE;
    }

    /**
     * Create a @see \AMQPQueue instance using a given @see \AMQPChannel.
     *
     * @param \AMQPChannel $channel
     * @param bool|false $declare If True then force the queue setup
     * @return \AMQPQueue
     */
    public function create(\AMQPChannel $channel, $declare = false)
    {
        $queue = new \AMQPQueue($channel);

        $queue->setName($this->name);
        $queue->setFlags($this->flags);

        // In some setups a empty array for setArguments will cause a segfault
        // so let's avoid that
        if (!empty($this->arguments)) {
            $queue->setArguments($this->arguments);
        }

        // Only setup a queue if we are forced or the queue is not durable
        if ($declare === true || $this->isDurable() === false) {
            $this->setup($queue);
        }

        return $queue;
    }

    /**
     * Setup a queue inside a AMQP server.
     * This will declare the queue and it's bindings
     *
     * @param \AMQPQueue $queue
     */
    protected function setup(\AMQPQueue $queue)
    {
        $queue->declareQueue();

        foreach ($this->binds as $exchange => $params) {
            $queue->bind($exchange, $params['routing_key'], $params['arguments']);
        }
    }
}
