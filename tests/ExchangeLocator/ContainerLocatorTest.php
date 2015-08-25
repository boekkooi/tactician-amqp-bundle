<?php
namespace Tests\Boekkooi\Bundle\AMQP\ExchangeLocator;

use Boekkooi\Bundle\AMQP\DependencyInjection\BoekkooiAMQPExtension;
use Boekkooi\Bundle\AMQP\Exception\MissingExchangeException;
use Boekkooi\Bundle\AMQP\LazyChannel;
use Boekkooi\Bundle\AMQP\LazyConnection;
use Boekkooi\Bundle\AMQP\LazyExchange;
use Boekkooi\Bundle\AMQP\ExchangeLocator\ContainerLocator;
use Boekkooi\Tactician\AMQP\AMQPAwareMessage;
use Boekkooi\Tactician\AMQP\Message;
use Mockery;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerInterface|Mockery\MockInterface
     */
    private $container;

    /**
     * @var ContainerLocator
     */
    private $locator;

    public function setUp()
    {
        $this->container = Mockery::mock(ContainerInterface::class);
        $this->locator = new ContainerLocator($this->container);
    }

    /**
     * @test
     */
    public function it_should_locate_a_message_exchange()
    {
        $message = $this->mockMessage();

        // Connection
        $amqpConnection = Mockery::mock(\AMQPConnection::class);
        $connection = Mockery::mock(LazyConnection::class);
        $connection
            ->shouldReceive('instance')
            ->atLeast()->once()
            ->andReturn($amqpConnection);

        $connectionServiceId = sprintf(BoekkooiAMQPExtension::SERVICE_VHOST_CONNECTION_ID, 'main');
        $this->container
            ->shouldReceive('get')
            ->atLeast()->once()
            ->with($connectionServiceId)
            ->andReturn($connection);

        // Channel
        $amqpChannel = Mockery::mock(\AMQPChannel::class);
        $channel = Mockery::mock(LazyChannel::class);
        $channel
            ->shouldReceive('create')
            ->atLeast()->once()
            ->with($amqpConnection)
            ->andReturn($amqpChannel);

        $channelServiceId = sprintf(BoekkooiAMQPExtension::SERVICE_VHOST_CHANNEL_ID, 'main');
        $this->container
            ->shouldReceive('get')
            ->atLeast()->once()
            ->with($channelServiceId)
            ->andReturn($channel);

        // Exchange
        $amqpExchange = Mockery::mock(\AMQPChannel::class);
        $exchange = Mockery::mock(LazyExchange::class);
        $exchange
            ->shouldReceive('create')
            ->atLeast()->once()
            ->with($amqpChannel)
            ->andReturn($amqpExchange);

        $exchangeServiceId = sprintf(BoekkooiAMQPExtension::SERVICE_VHOST_EXCHANGE_ID, 'main', 'ex');
        $this->container
            ->shouldReceive('has')
            ->atLeast()->once()
            ->with($exchangeServiceId)
            ->andReturn(true);
        $this->container
            ->shouldReceive('get')
            ->atLeast()->once()
            ->with($exchangeServiceId)
            ->andReturn($exchange);

        $this->assertSame(
            $amqpExchange,
            $this->locator->getExchangeForMessage($message)
        );
    }

    /**
     * @test
     */
    public function it_should_fail_if_the_message_is_not_amqp_aware()
    {
        $this->setExpectedException(MissingExchangeException::class);

        /** @var Mockery\MockInterface|Message $message */
        $message = Mockery::mock(Message::class);

        $this->container->shouldNotReceive('has')->withAnyArgs();

        $this->locator->getExchangeForMessage($message);
    }

    /**
     * @test
     */
    public function it_should_fail_if_the_service_is_not_found()
    {
        $this->setExpectedException(MissingExchangeException::class);

        $message = $this->mockMessage('/v', 'e');
        $exchangeServiceId = sprintf(BoekkooiAMQPExtension::SERVICE_VHOST_EXCHANGE_ID, '/v', 'e');

        $this->container->shouldReceive('has')->with($exchangeServiceId)->andReturn(false);

        $this->locator->getExchangeForMessage($message);
    }

    /**
     * @param string $vhost
     * @param string $exchange
     * @return AMQPAwareMessage|Mockery\MockInterface
     */
    private function mockMessage($vhost = 'main', $exchange = 'ex')
    {
        $message = Mockery::mock(AMQPAwareMessage::class);
        $message->shouldReceive('getVHost')->andReturn($vhost);
        $message->shouldReceive('getExchange')->andReturn($exchange);

        return $message;
    }
}
