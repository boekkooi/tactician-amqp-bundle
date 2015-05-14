<?php
namespace Boekkooi\Bundle\AMQP\Exception;

use Boekkooi\Tactician\AMQP\Exception\Exception;
use Boekkooi\Tactician\AMQP\Message;

class CommandTransformationException extends \RuntimeException implements Exception
{
    /**
     * @var mixed
     */
    private $command;

    /**
     * @param mixed $message
     * @param mixed $command
     * @return static
     */
    public static function invalidMessageFromTransformer($message, $command)
    {
        $exception = new static(sprintf(
            'A %s was expect to be returned by the Transformer for command %s but %s was received.',
            Message::class,
            (is_object($command) ? get_class($command) : gettype($command)),
            (is_object($message) ? get_class($message) : gettype($message))
        ));
        $exception->command = $command;

        return $exception;
    }

    /**
     * @param mixed $command
     * @return static
     */
    public static function expectedObject($command)
    {
        $exception = new static(sprintf(
            'A object was expect but got a %s',
            gettype($command)
        ));
        $exception->command = $command;

        return $exception;
    }

    /**
     * @param mixed $command
     * @param string[] $knownCommands
     * @return static
     */
    public static function unknownCommand($command, array $knownCommands)
    {
        $exception = new static(sprintf(
            'No configuration found for command %s, the following commands are supported: %s',
            get_class($command),
            implode(', ', $knownCommands)
        ));
        $exception->command = $command;

        return $exception;
    }

    /**
     * @param mixed $command
     * @param string $format
     * @return static
     */
    public static function unsupportedFormat($command, $format)
    {
        $exception = new static(sprintf(
            'Unsupported serialization format %s provided for the command',
            $format
        ));
        $exception->command = $command;

        return $exception;
    }

    /**
     * Returns the command that failed to transform
     *
     * @return mixed
     */
    public function getCommand()
    {
        return $this->command;
    }
}
