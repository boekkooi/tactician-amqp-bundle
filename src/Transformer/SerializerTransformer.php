<?php
namespace Boekkooi\Bundle\AMQP\Transformer;

use Boekkooi\Bundle\AMQP\Exception\EnvelopeTransformationException;
use Boekkooi\Bundle\AMQP\Exception\InvalidArgumentException;
use Boekkooi\Bundle\AMQP\Exception\CommandTransformationException;
use Boekkooi\Bundle\AMQP\AMQPMessage;
use Boekkooi\Tactician\AMQP\Transformer\EnvelopeTransformer;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;

class SerializerTransformer implements CommandTransformer, EnvelopeTransformer
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
     * @var array
     */
    private $commandData;

    /**
     * @param SerializerInterface $serializer
     * @param string $format
     */
    public function __construct(SerializerInterface $serializer, $format)
    {
        $this->serializer = $serializer;
        $this->format = $format;
        $this->commandData = [];
    }

    /**
     * Register a command with the transformer
     *
     * @param $class
     * @param array $info
     */
    public function addCommand($class, array $info)
    {
        if (!isset($info['vhost'])) {
            throw InvalidArgumentException::missingCommandInfo($class, 'vhost');
        }
        if (!is_string($info['vhost'])) {
            throw InvalidArgumentException::commandExpectedString($class, 'vhost', $info['vhost']);
        }
        if (!isset($info['exchange'])) {
            throw InvalidArgumentException::missingCommandInfo($class, 'exchange');
        }
        if (!is_string($info['exchange'])) {
            throw InvalidArgumentException::commandExpectedString($class, 'exchange', $info['exchange']);
        }

        $routingKey = (isset($info['routing_key']) ? $info['routing_key'] : null);
        $flags = (isset($info['flags']) ? $info['flags'] : AMQP_NOPARAM);
        $attributes = (isset($info['attributes']) ? $info['attributes'] : []);

        if (!is_string($routingKey) && $routingKey !== null) {
            throw InvalidArgumentException::commandExpectedStringOrNull($class, 'routing_key', $routingKey);
        }
        if (!is_int($flags)) {
            throw InvalidArgumentException::commandExpectedIntegerOrEmpty($class, 'flags', $flags);
        }
        if (!is_array($attributes)) {
            throw InvalidArgumentException::commandExpectedArrayOrEmpty($class, 'attributes', $attributes);
        }

        $info['routing_key'] = $routingKey;
        $info['flags'] = $flags;
        $info['attributes'] = $attributes;

        $class = ltrim($class, '\\');
        $this->commandData[$class] = $info;
    }

    /**
     * Register a set of commands with the transformer
     *
     * @param array $commands
     */
    public function addCommands(array $commands)
    {
        foreach ($commands as $class => $info) {
            $this->addCommand($class, $info);
        }
    }

    /**
     * Returns all registered command metadata
     *
     * @return array
     */
    public function getCommands()
    {
        return $this->commandData;
    }

    /**
     * Returns a Message instance representation of a command
     *
     * @param mixed $command The command to transform
     * @return AMQPMessage
     */
    public function transformCommandToMessage($command)
    {
        if (!is_object($command)) {
            throw CommandTransformationException::expectedObject($command);
        }
        if (!isset($this->commandData[get_class($command)])) {
            throw CommandTransformationException::unknownCommand($command, array_keys($this->commandData));
        }
        if ($this->serializer instanceof EncoderInterface && !$this->serializer->supportsEncoding($this->format)) {
            throw CommandTransformationException::unsupportedFormat($command, $this->format);
        }

        $info = $this->commandData[get_class($command)];

        return new AMQPMessage(
            $info['vhost'],
            $info['exchange'],
            $this->serializer->serialize($command, $this->format),
            $info['routing_key'],
            $info['flags'],
            $this->resolveMessageAttributes($command, $info)
        );
    }

    /**
     * Returns a Command based on the provided envelope
     *
     * @param \AMQPEnvelope $envelope The evelope to transform
     * @return mixed
     */
    public function transformEnvelopeToCommand(\AMQPEnvelope $envelope)
    {
        $format = $this->format;
        if (strlen($envelope->getContentType()) > strlen('application/')) {
            $format = substr($envelope->getContentType(), strlen('application/'));
        }

        if ($this->serializer instanceof DecoderInterface && !$this->serializer->supportsDecoding($format)) {
            throw EnvelopeTransformationException::unsupportedFormat($envelope, $format);
        }

        $class = $envelope->getHeader('x-symfony-command');
        if ($class === false) {
            throw EnvelopeTransformationException::noCommandHeader($envelope, 'x-symfony-command');
        }
        if (!class_exists($class)) {
            throw EnvelopeTransformationException::unknownClass($envelope, $class);
        }

        return $this->serializer->deserialize($envelope->getBody(), $class, $format);
    }

    /**
     * @param $command
     * @param $info
     * @return array
     */
    private function resolveMessageAttributes($command, $info)
    {
        $defaultAttributes = [
            'content_type' => 'application/' . strtolower($this->format),
            'headers' => [
                'x-symfony-command' => get_class($command)
            ]
        ];
        if (empty($info['attributes'])) {
            return $defaultAttributes;
        }

        $attributes = array_merge($defaultAttributes, $info['attributes']);
        if (!isset($attributes['headers']['x-symfony-command'])) {
            $attributes['headers']['x-symfony-command'] = $defaultAttributes['headers']['x-symfony-command'];
        }

        return $attributes;
    }
}
