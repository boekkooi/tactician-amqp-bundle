<?php
namespace Boekkooi\Bundle\AMQP\Exception;

use Boekkooi\Tactician\AMQP\Exception\Exception;

class CommandConfigurationException extends \InvalidArgumentException implements Exception
{
    public static function forInvalidVhost($param)
    {
        return new static(sprintf(
            '`vhost` information must contains a string but got "%s"',
            is_object($param) ? get_class($param) : gettype($param)
        ));
    }

    public static function forInvalidExchange($param)
    {
        return new static(sprintf(
            '`exchange` information must contains a string but got "%s"',
            is_object($param) ? get_class($param) : gettype($param)
        ));
    }

    public static function forInvalidRoutingKey($param)
    {
        return new static(sprintf(
            '`routing_key` information must be a string or NULL but got "%s"',
            is_object($param) ? get_class($param) : gettype($param)
        ));
    }

    public static function forInvalidFlags($param)
    {
        return new static(sprintf(
            '`flags` information must be a int but got "%s"',
            is_object($param) ? get_class($param) : gettype($param)
        ));
    }
}
