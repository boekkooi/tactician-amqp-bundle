<?php
namespace Boekkooi\Bundle\AMQP\Transformer;

use Boekkooi\Bundle\AMQP\CommandMessage;
use Boekkooi\Bundle\AMQP\CommandConfiguration;
use Boekkooi\Bundle\AMQP\Exception\CommandTransformationException;
use Boekkooi\Tactician\AMQP\Transformer\CommandTransformer as CommandTransformerInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;

class CommandTransformer implements CommandTransformerInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var string
     */
    private $format;
    /**
     * @var string
     */
    private $commandHeaderName;

    /**
     * @var CommandConfiguration[]
     */
    private $commands = [];

    /**
     * Constructor
     *
     * @param SerializerInterface $serializer
     * @param string $format The default serialize format
     * @param string $commandHeaderName The name of the amqp header to identify the command
     */
    public function __construct(SerializerInterface $serializer, $format, $commandHeaderName = 'x-symfony-command')
    {
        $this->serializer = $serializer;
        $this->format = $format;
        $this->commandHeaderName = $commandHeaderName;
    }

    /**
     * Register a command configuration.
     *
     * @param CommandConfiguration $command
     */
    public function registerCommand(CommandConfiguration $command)
    {
        $this->commands[$command->getClass()] = $command;
    }

    /**
     * Register a set of command configurations.
     *
     * @param CommandConfiguration[] $commands
     */
    public function registerCommands($commands)
    {
        foreach ($commands as $command) {
            $this->registerCommand($command);
        }
    }

    /**
     * Returns a Message instance representation of a command
     *
     * @param mixed $command The command to transform
     * @return CommandMessage
     */
    public function transformCommandToMessage($command)
    {
        if (!is_object($command)) {
            throw CommandTransformationException::expectedObject($command);
        }
        if (!isset($this->commands[get_class($command)])) {
            throw CommandTransformationException::unknownCommand($command, array_keys($this->commands));
        }
        if ($this->serializer instanceof EncoderInterface && !$this->serializer->supportsEncoding($this->format)) {
            throw CommandTransformationException::unsupportedFormat($command, $this->format);
        }

        $info = $this->commands[get_class($command)];

        return new CommandMessage(
            $info->getVhost(),
            $info->getExchange(),
            $this->serializer->serialize($command, $this->format),
            $info->getRoutingKey(),
            $info->getFlags(),
            $this->resolveMessageAttributes($command, $info)
        );
    }

    /**
     * @param object $command
     * @param CommandConfiguration $config
     * @return array
     */
    private function resolveMessageAttributes($command, CommandConfiguration $config)
    {
        $defaultAttributes = [
            'content_type' => 'application/' . strtolower($this->format),
            'headers' => [
                $this->commandHeaderName => get_class($command)
            ]
        ];
        if (empty($config->getAttributes())) {
            return $defaultAttributes;
        }

        $attributes = array_merge($defaultAttributes, $config->getAttributes());
        if (!isset($attributes['headers'][$this->commandHeaderName])) {
            $attributes['headers'][$this->commandHeaderName] = $defaultAttributes['headers'][$this->commandHeaderName];
        }

        return $attributes;
    }
}
