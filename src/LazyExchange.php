<?php
namespace Boekkooi\Bundle\AMQP;

class LazyExchange
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $type;
    /**
     * @var int
     */
    private $flags;
    /**
     * @var array
     */
    private $arguments;

    public function __construct($name, $type = AMQP_EX_TYPE_DIRECT, $passive = true, $durable = false, array $arguments = [])
    {
        $this->name = $name;
        $this->type = $type;
        $this->flags = (
            ($passive ? AMQP_PASSIVE : AMQP_NOPARAM) |
            ($durable ? AMQP_DURABLE : AMQP_NOPARAM)
        );
        $this->arguments = $arguments;
    }

    /**
     * Get the name of the exchange
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Indicates if the exchange is durable
     *
     * @return bool
     */
    public function isDurable()
    {
        return ($this->flags & AMQP_DURABLE) === AMQP_DURABLE;
    }

    /**
     * Create a @see \AMQPExchange instance using a given @see \AMQPChannel.
     *
     * @param \AMQPChannel $channel
     * @param bool|false $declare If True then force the queue setup
     * @return \AMQPExchange
     */
    public function create(\AMQPChannel $channel, $declare = false)
    {
        $exchange = new \AMQPExchange($channel);

        $exchange->setName($this->name);
        $exchange->setType($this->type);
        $exchange->setFlags($this->flags);

        // In some setups a empty array for setArguments will cause a segfault
        // so let's avoid that
        if (!empty($this->arguments)) {
            $exchange->setArguments($this->arguments);
        }

        // Only declare a exchange if we are forced or the queue is not durable
        if ($declare || !$this->isDurable()) {
            $exchange->declareExchange();
        }

        return $exchange;
    }
}
