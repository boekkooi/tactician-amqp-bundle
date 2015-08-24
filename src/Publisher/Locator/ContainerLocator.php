<?php
namespace Boekkooi\Bundle\AMQP\Publisher\Locator;

use Boekkooi\Bundle\AMQP\DependencyInjection\BoekkooiAMQPExtension;
use Boekkooi\Bundle\AMQP\Exception\MissingExchangeException;
use Boekkooi\Tactician\AMQP\Message;
use Boekkooi\Tactician\AMQP\Publisher\Locator\ExchangeLocator;
use Boekkooi\Tactician\AMQP\AMQPAwareMessage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Symfony Dependency Injection exchange locator.
 */
class ContainerLocator implements ExchangeLocator
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var \AMQPExchange[]
     */
    private $exchanges;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeForMessage(Message $message)
    {
        if (!$message instanceof AMQPAwareMessage) {
            throw MissingExchangeException::unsupportedMessage($message);
        }

        $serviceId = $this->getExchangeServiceId($message);
        if (!$this->container->has($serviceId)) {
            throw MissingExchangeException::noService($message, $serviceId);
        }

        if (
            !isset($this->exchanges[$serviceId]) ||
            !$this->exchanges[$serviceId]->getChannel()->isConnected()
        ) {
            /** @var \Boekkooi\Bundle\AMQP\LazyExchange $exchange */
            $exchange = $this->container->get($serviceId);
            $this->exchanges[$serviceId] = $exchange->create(
                $this->initializeChannel($message)
            );
        }

        return $this->exchanges[$serviceId];
    }

    /**
     * Get the @see \AMQPChannel instance for a @see AMQPAwareMessage instance.
     *
     * @param AMQPAwareMessage $message
     * @return \AMQPChannel
     */
    protected function initializeChannel(AMQPAwareMessage $message)
    {
        /** @var \Boekkooi\Bundle\AMQP\LazyChannel $channel */
        $channel = $this->container->get(sprintf(
            BoekkooiAMQPExtension::SERVICE_VHOST_CHANNEL_ID,
            $message->getVHost()
        ));

        return $channel->create(
            $this->getConnection($message)
        );
    }

    /**
     * Get the @see \AMQPConnection instance for a @see AMQPAwareMessage instance.
     *
     * @param AMQPAwareMessage $message
     * @return \AMQPConnection
     */
    protected function getConnection(AMQPAwareMessage $message)
    {
        /** @var \Boekkooi\Bundle\AMQP\LazyConnection $connection */
        $connection = $this->container->get(sprintf(
            BoekkooiAMQPExtension::SERVICE_VHOST_CONNECTION_ID,
            $message->getVHost()
        ));

        return $connection->instance();
    }

    /**
     * Get the exchange DI container id for a @see AMQPAwareMessage instance.
     *
     * @param AMQPAwareMessage $message
     * @return string
     */
    protected function getExchangeServiceId(AMQPAwareMessage $message)
    {
        return sprintf(
            BoekkooiAMQPExtension::SERVICE_VHOST_EXCHANGE_ID,
            $message->getVHost(),
            $message->getExchange()
        );
    }
}
