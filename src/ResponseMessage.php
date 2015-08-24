<?php
namespace Boekkooi\Bundle\AMQP;

use Boekkooi\Tactician\AMQP\Message;

class ResponseMessage implements Message
{
    private $message;
    private $flags;
    private $attributes;

    public function __construct(
        $message,
        $flags = AMQP_NOPARAM,
        array $attributes = []
    ) {
        $this->message = $message;
        $this->flags = $flags;
        $this->attributes = $attributes;
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
     * Returns the routing key
     * For a response this is always NULL
     *
     * @return null
     */
    public function getRoutingKey()
    {
        return null;
    }
}
