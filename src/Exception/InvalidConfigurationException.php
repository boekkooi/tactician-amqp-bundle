<?php
namespace Boekkooi\Bundle\AMQP\Exception;

class InvalidConfigurationException extends \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
{
    public static function noConnectionForVHost($connection, $vhost)
    {
        $exception = new static(sprintf(
            'A unknown connection %s was given for VHost %s.',
            $connection,
            $vhost
        ));

        return $exception;
    }

    public static function unknownVHostForCommand($command, $vhost)
    {
        $exception = new static(sprintf(
            'A unknown vhost %s was given for command %s.',
            $vhost,
            $command
        ));

        return $exception;
    }

    public static function unknownExchangeForCommand($command, $vhost, $exchange)
    {
        $exception = new static(sprintf(
            'A unknown exchange %s under vhost %s was given for command %s.',
            $vhost,
            $exchange,
            $command
        ));

        return $exception;
    }
}
