<?php
namespace Boekkooi\Bundle\AMQP;

use Boekkooi\Bundle\AMQP\Exception\CommandConfigurationException;

/**
 * @author Warnar Boekkooi <warnar@boekkooi.net>
 */
class CommandConfiguration
{
    /**
     * @var string
     */
    private $class;
    /**
     * @var string
     */
    private $vhost;
    /**
     * @var string
     */
    private $exchange;
    /**
     * @var null
     */
    private $routingKey;
    /**
     * @var int
     */
    private $flags;
    /**
     * @var array
     */
    private $attributes;

    public function __construct($class, $vhost, $exchange, $routingKey = null, $flags = AMQP_NOPARAM, array $attributes = [])
    {
        if (!is_string($vhost)) {
            throw CommandConfigurationException::forInvalidVhost(
                $vhost
            );
        }
        if (!is_string($exchange)) {
            throw CommandConfigurationException::forInvalidExchange(
                $exchange
            );
        }
        if (!is_string($routingKey) && $routingKey !== null) {
            throw CommandConfigurationException::forInvalidRoutingKey($routingKey);
        }
        if (!is_int($flags)) {
            throw CommandConfigurationException::forInvalidFlags($flags);
        }

        $this->vhost = $vhost;
        $this->exchange = $exchange;
        $this->routingKey = $routingKey;
        $this->flags = $flags;
        $this->attributes = $attributes;
        $this->class = ltrim($class, '\\');
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return string
     */
    public function getVhost()
    {
        return $this->vhost;
    }

    /**
     * @return string
     */
    public function getExchange()
    {
        return $this->exchange;
    }

    /**
     * @return null
     */
    public function getRoutingKey()
    {
        return $this->routingKey;
    }

    /**
     * @return int
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
}
