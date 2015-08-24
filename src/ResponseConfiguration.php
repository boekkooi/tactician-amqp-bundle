<?php
namespace Boekkooi\Bundle\AMQP;

use Boekkooi\Bundle\AMQP\Exception\ResponseConfigurationException;

class ResponseConfiguration
{
    /**
     * @var string
     */
    private $class;
    /**
     * @var int
     */
    private $flags;
    /**
     * @var array
     */
    private $attributes;

    public function __construct($class, $flags = AMQP_NOPARAM, array $attributes = [])
    {
        if (!is_int($flags)) {
            throw ResponseConfigurationException::forInvalidFlags($flags);
        }
        if (!is_array($attributes)) {
            throw ResponseConfigurationException::forInvalidAttributes($attributes);
        }

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
