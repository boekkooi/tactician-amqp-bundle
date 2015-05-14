<?php
namespace Boekkooi\Bundle\AMQP\Exception;

use Boekkooi\Tactician\AMQP\Exception\Exception;

class EnvelopeTransformationException extends \RuntimeException implements Exception
{
    /**
     * @var \AMQPEnvelope
     */
    private $envelope;

    /**
     * @param \AMQPEnvelope $envelope
     * @param string $header
     * @return static
     */
    public static function noCommandHeader(\AMQPEnvelope $envelope, $header)
    {
        $exception = new static(sprintf(
            'No %s header for the provided envelope',
            $header
        ));
        $exception->envelope = $envelope;

        return $exception;
    }

    /**
     * @param \AMQPEnvelope $envelope
     * @param string $class
     * @return static
     */
    public static function unknownClass(\AMQPEnvelope $envelope, $class)
    {
        $exception = new static(sprintf(
            'Unknown class %s provided by the envelope for transformation',
            $class
        ));
        $exception->envelope = $envelope;

        return $exception;
    }

    /**
     * @param \AMQPEnvelope $envelope
     * @param string $format
     * @return static
     */
    public static function unsupportedFormat(\AMQPEnvelope $envelope, $format)
    {
        $exception = new static(sprintf(
            'Unsupported derialization format %s provided by the envelope',
            $format
        ));
        $exception->envelope = $envelope;

        return $exception;
    }

    /**
     * Returns the envelope that failed to transform
     *
     * @return mixed
     */
    public function getEnvelope()
    {
        return $this->envelope;
    }
}
