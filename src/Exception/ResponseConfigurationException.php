<?php
namespace Boekkooi\Bundle\AMQP\Exception;

use Boekkooi\Tactician\AMQP\Exception\Exception;

class ResponseConfigurationException extends \InvalidArgumentException implements Exception
{
    public static function forInvalidFlags($param)
    {
        return new static(sprintf(
            '`flags` information must be a int but got "%s"',
            is_object($param) ? get_class($param) : gettype($param)
        ));
    }

    public static function forInvalidAttributes($param)
    {
        return new static(sprintf(
            '`attributes` information must be a array or empty but got "%s"',
            is_object($param) ? get_class($param) : gettype($param)
        ));
    }
}
