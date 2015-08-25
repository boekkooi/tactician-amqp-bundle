<?php
namespace Boekkooi\Bundle\AMQP\Exception;

use Boekkooi\Tactician\AMQP\Exception\MissingPublisherException as BaseException;
use Boekkooi\Tactician\AMQP\Message;

class MissingPublisherException extends BaseException
{

    public static function noHeaderInMessage(Message $message, $header)
    {
        $exception = new self(sprintf(
            'No header "%s" was found in the message attributes headers',
            $header
        ));
        $exception->tacticianMessage = $message;

        return $exception;
    }

    public static function noKnownPublisherFor($message)
    {
        $exception = new self('Unable to find a publisher for the message');
        $exception->tacticianMessage = $message;

        return $exception;
    }
}
