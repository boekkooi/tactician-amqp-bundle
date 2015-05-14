<?php
namespace Boekkooi\Bundle\AMQP\Publisher\Locator;

use Boekkooi\Bundle\AMQP\DependencyInjection\BoekkooiAMQPExtension;
use Boekkooi\Bundle\AMQP\Exception\MissingExchangeException;
use Boekkooi\Tactician\AMQP\Message;
use Boekkooi\Tactician\AMQP\Publisher\Locator\ExchangeLocator;
use Boekkooi\Tactician\AMQP\AMQPAwareMessage;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerLocator implements ExchangeLocator
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeForMessage(Message $message)
    {
        $serviceId = $this->getExchangeServiceId($message);

        if (!$this->container->has($serviceId)) {
            throw MissingExchangeException::noService($message, $serviceId);
        }
        return $this->container->get($serviceId);
    }

    protected function getExchangeServiceId(Message $message)
    {
        if (!$message instanceof AMQPAwareMessage) {
            throw MissingExchangeException::unsupportedMessage($message);
        }

        return sprintf(
            BoekkooiAMQPExtension::SERVICE_VHOST_EXCHANGE_ID,
            $message->getVHost(),
            $message->getExchange()
        );
    }
}
