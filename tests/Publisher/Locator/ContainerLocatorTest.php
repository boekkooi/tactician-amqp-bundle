<?php
namespace Tests\Boekkooi\Bundle\AMQP\Publisher\Locator;

use Boekkooi\Bundle\AMQP\DependencyInjection\BoekkooiAMQPExtension;
use Boekkooi\Bundle\AMQP\Exception\MissingExchangeException;
use Boekkooi\Bundle\AMQP\Publisher\Locator\ContainerLocator;
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

        $exchangeServiceId = sprintf(BoekkooiAMQPExtension::SERVICE_VHOST_EXCHANGE_ID, '/', 'ex');
        $exchange = Mockery::mock(\AMQPExchange::class);

        $this->container->shouldReceive('has')->with($exchangeServiceId)->andReturn(true);
        $this->container->shouldReceive('get')->with($exchangeServiceId)->andReturn($exchange);

        $this->assertSame(
            $exchange,
            $this->locator->getExchangeForMessage($message)
        );
    }

    /**
     * @test
     */
    public function it_should_fail_if_the_message_is_not_amqp_aware()
    {
        $this->setExpectedException(MissingExchangeException::class);

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
    private function mockMessage($vhost = '/', $exchange = 'ex')
    {
        $message = Mockery::mock(AMQPAwareMessage::class);
        $message->shouldReceive('getVHost')->andReturn($vhost);
        $message->shouldReceive('getExchange')->andReturn($exchange);

        return $message;
    }
}
