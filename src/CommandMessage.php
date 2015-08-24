<?php
namespace Boekkooi\Bundle\AMQP;

use Boekkooi\Tactician\AMQP\AMQPAwareMessage;

class CommandMessage implements AMQPAwareMessage
{
    private $message;
    private $routingKey;
    private $flags;
    private $attributes;
    private $vhost;
    private $exchange;

    public function __construct(
        $vhost,
        $exchange,
        $message,
        $routingKey = null,
        $flags = AMQP_IMMEDIATE,
        array $attributes = []
    ) {
        $this->message = $message;
        $this->routingKey = $routingKey;
        $this->flags = $flags;
        $this->attributes = $attributes;
        $this->vhost = $vhost;
        $this->exchange = $exchange;
    }

    /**
     * @inheritdoc
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @inheritdoc
     */
    public function getRoutingKey()
    {
        return $this->routingKey;
    }

    /**
     * @inheritdoc
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * @inheritdoc
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @inheritdoc
     */
    public function getVHost()
    {
        return $this->vhost;
    }

    /**
     * @inheritdoc
     */
    public function getExchange()
    {
        return $this->exchange;
    }
}
