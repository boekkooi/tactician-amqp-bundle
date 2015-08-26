<?php
namespace Boekkooi\Bundle\AMQP\PublisherLocator;

use Boekkooi\Bundle\AMQP\Exception\MissingPublisherException;
use Boekkooi\Tactician\AMQP\Message;
use Boekkooi\Tactician\AMQP\Publisher\Locator\PublisherLocator;
use Boekkooi\Tactician\AMQP\Publisher\Publisher;

class HeaderPublisherLocator implements PublisherLocator
{
    /**
     * @var string
     */
    private $headerName;
    /**
     * @var string[][]
     */
    private $valueMap = [];
    /**
     * @var Publisher[]
     */
    private $publishers = [];

    public function __construct($headerName = 'x-symfony-command')
    {
        $this->headerName = $headerName;
    }

    public function registerPublisher(Publisher $publisher, array $headerValues = [])
    {
        $hash = spl_object_hash($publisher);

        $this->publishers[$hash] = $publisher;
        $this->valueMap[$hash] = $headerValues;
    }

    /**
     * Retrieves the publisher for a specified message
     *
     * @param Message $message
     *
     * @return Publisher
     *
     * @throws MissingPublisherException
     */
    public function getPublisherForMessage(Message $message)
    {
        $attributes = $message->getAttributes();
        if (!isset($attributes['headers']) || !isset($attributes['headers'][$this->headerName])) {
            throw MissingPublisherException::noHeaderInMessage($message, $this->headerName);
        }

        $value = $attributes['headers'][$this->headerName];
        foreach ($this->valueMap as $hash => $values) {
            if (!in_array($value, $values, true)) {
                continue;
            }

            return $this->publishers[$hash];
        }

        throw MissingPublisherException::noKnownPublisherFor($message);
    }
}
