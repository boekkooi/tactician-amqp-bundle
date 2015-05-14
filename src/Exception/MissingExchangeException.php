<?php
namespace Boekkooi\Bundle\AMQP\Exception;

use Boekkooi\Tactician\AMQP\Message;
use Boekkooi\Tactician\AMQP\AMQPAwareMessage;

class MissingExchangeException extends \Boekkooi\Tactician\AMQP\Exception\MissingExchangeException
{
    public static function unsupportedMessage(Message $message)
    {
        $exception = new static(sprintf(
            'Unsupported message for type %s expected %s',
            get_class($message),
            AMQPAwareMessage::class
        ));
        $exception->tacticianMessage = $message;

        return $exception;
    }

    public static function noService($message, $serviceId)
    {
        $exception = new static(sprintf(
            'Unable to locate exchange service %s for command %s',
            $serviceId,
            get_class($message)
        ));
        $exception->tacticianMessage = $message;

        return $exception;
    }
}
