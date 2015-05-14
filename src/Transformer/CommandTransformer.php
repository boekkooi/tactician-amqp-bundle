<?php
namespace Boekkooi\Bundle\AMQP\Transformer;

use Boekkooi\Tactician\AMQP\Message;

/**
 * A transformer that transforms a command into a @see \Boekkooi\Tactician\AMQP\Message instance
 */
interface CommandTransformer
{
    /**
     * Returns a Message instance representation of a command
     *
     * @param mixed $command The command to transform
     * @return Message
     */
    public function transformCommandToMessage($command);
}
