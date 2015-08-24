<?php
namespace Boekkooi\Bundle\AMQP\Exception;

class MissingQueueException extends \InvalidArgumentException
{
    public static function noService($serviceId)
    {
        $exception = new static(sprintf(
            'Unable to locate queue service %s',
            $serviceId
        ));

        return $exception;
    }
}
