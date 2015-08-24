<?php
namespace Boekkooi\Bundle\AMQP\Transformer;

use Boekkooi\Bundle\AMQP\Exception\EnvelopeTransformationException;
use \Boekkooi\Tactician\AMQP\Transformer\EnvelopeTransformer as EnvelopeTransformerInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\SerializerInterface;

class EnvelopeTransformer implements EnvelopeTransformerInterface
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
     * Constructor
     *
     * @param SerializerInterface $serializer
     * @param string $format The default deserialize format
     * @param string $commandHeaderName The name of the amqp header to identify the command
     */
    public function __construct(SerializerInterface $serializer, $format, $commandHeaderName = 'x-symfony-command')
    {
        $this->serializer = $serializer;
        $this->format = $format;
        $this->commandHeaderName = $commandHeaderName;
    }

    /**
     * @inheritdoc
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

        $class = $envelope->getHeader($this->commandHeaderName);
        if ($class === false) {
            throw EnvelopeTransformationException::noCommandHeader($envelope, $this->commandHeaderName);
        }
        if (!class_exists($class)) {
            throw EnvelopeTransformationException::unknownClass($envelope, $class);
        }

        return $this->serializer->deserialize($envelope->getBody(), $class, $format);
    }
}
