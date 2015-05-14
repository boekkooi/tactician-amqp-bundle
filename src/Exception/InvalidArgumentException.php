<?php
namespace Boekkooi\Bundle\AMQP\Exception;

use Boekkooi\Tactician\AMQP\Exception\Exception;

class InvalidArgumentException extends \InvalidArgumentException implements Exception
{
    public static function missingCommandInfo($class, $argument)
    {
        return new static(sprintf(
            'Command information for %s must contains %s',
            $class,
            $argument
        ));
    }

    public static function commandExpectedString($class, $argument, $providedValue)
    {
        return new static(sprintf(
            'Command information for %s must be a string but a %s was provided',
            $class,
            $argument,
            gettype($providedValue)
        ));
    }

    public static function commandExpectedStringOrNull($class, $argument, $providedValue)
    {
        return new static(sprintf(
            'Command information for %s must be a string or null but a %s was provided',
            $class,
            $argument,
            gettype($providedValue)
        ));
    }

    public static function commandExpectedIntegerOrEmpty($class, $argument, $providedValue)
    {
        return new static(sprintf(
            'Command information for %s must be a integer (or it should not be set) but a %s was provided',
            $class,
            $argument,
            gettype($providedValue)
        ));
    }

    public static function commandExpectedArrayOrEmpty($class, $argument, $providedValue)
    {
        return new static(sprintf(
            'Command information for %s must be a array integer (or it should not be set) but a %s was provided',
            $class,
            $argument,
            gettype($providedValue)
        ));
    }
}
