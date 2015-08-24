<?php
namespace Boekkooi\Bundle\AMQP\Transformer;

use Boekkooi\Bundle\AMQP\ResponseMessage;
use Boekkooi\Bundle\AMQP\ResponseConfiguration;
use Boekkooi\Tactician\AMQP\Transformer\ResponseTransformer as ResponseTransformerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ResponseTransformer implements ResponseTransformerInterface
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;
    /**
     * @var string
     */
    protected $format;
    /**
     * @var string
     */
    private $responseHeaderName;
    /**
     * @var ResponseConfiguration[]
     */
    private $responses;

    /**
     * Constructor
     *
     * @param SerializerInterface $serializer
     * @param string $format The serialize format
     * @param string $responseHeaderName The name of the amqp header to identify the response
     */
    public function __construct(SerializerInterface $serializer, $format, $responseHeaderName = 'x-symfony-response')
    {
        $this->serializer = $serializer;
        $this->format = $format;
        $this->responseHeaderName = $responseHeaderName;
    }

    /**
     * Register a response configuration.
     *
     * @param ResponseConfiguration $response
     */
    public function registerResponse(ResponseConfiguration $response)
    {
        $this->responses[$response->getClass()] = $response;
    }

    /**
     * Register a set of response configurations.
     *
     * @param ResponseConfiguration[] $responses
     */
    public function registerResponses($responses)
    {
        foreach ($responses as $response) {
            $this->registerResponse($response);
        }
    }

    /**
     * @inheritdoc
     */
    public function transformCommandResponse($response)
    {
        $flags = AMQP_NOPARAM;
        $config = null;
        if (is_object($response) && isset($this->responses[get_class($response)])) {
            $config = $this->responses[get_class($response)];
            $flags = $config->getFlags();
        }

        return new ResponseMessage(
            $this->serializer->serialize($response, $this->format),
            $flags,
            $this->resolveMessageAttributes($response, $config)
        );
    }

    /**
     * @param mixed $response
     * @param ResponseConfiguration $config
     * @return array
     */
    private function resolveMessageAttributes($response, ResponseConfiguration $config = null)
    {
        $defaultAttributes = [
            'content_type' => 'application/' . strtolower($this->format)
        ];
        if (empty($config) || empty($config->getAttributes())) {
            return $defaultAttributes;
        }

        $attributes = array_merge($defaultAttributes, $config->getAttributes());
        if (!isset($attributes['headers'][$this->responseHeaderName])) {
            $attributes['headers'][$this->responseHeaderName] = get_class($response);
        }

        return $attributes;
    }
}
