<?php
namespace Tests\Boekkooi\Bundle\AMQP\QueueLocator;

use Boekkooi\Bundle\AMQP\QueueLocator\ContainerLocator;
use Boekkooi\Bundle\AMQP\DependencyInjection\BoekkooiAMQPExtension;
use Boekkooi\Bundle\AMQP\Exception\MissingQueueException;
use Boekkooi\Bundle\AMQP\LazyQueue;
use Mockery;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_returns_a_know_queue_by_its_name()
    {
        $queue = Mockery::mock(LazyQueue::class);
        $queueServiceId = sprintf(
            BoekkooiAMQPExtension::SERVICE_VHOST_QUEUE_ID,
            'vhost',
            'my_queue'
        );

        /** @var ContainerInterface|Mockery\MockInterface $container */
        $container = Mockery::mock(ContainerInterface::class);
        $container
            ->shouldReceive('has')
            ->atLeast()->once()
            ->with($queueServiceId)
            ->andReturn(true);
        $container
            ->shouldReceive('get')
            ->atLeast()->once()
            ->with($queueServiceId)
            ->andReturn($queue);

        $locator = new ContainerLocator($container, 'vhost');
        $this->assertSame(
            $queue,
            $locator->getQueueByName('my_queue')
        );
    }

    /**
     * @test
     */
    public function it_throw_a_exception_for_a_unknown_queue()
    {
        $queueServiceId = sprintf(
            BoekkooiAMQPExtension::SERVICE_VHOST_QUEUE_ID,
            'vhost',
            'unknown_queue'
        );

        /** @var ContainerInterface|Mockery\MockInterface $container */
        $container = Mockery::mock(ContainerInterface::class);
        $container
            ->shouldReceive('has')
            ->atLeast()->once()
            ->with($queueServiceId)
            ->andReturn(false);
        $container
            ->shouldNotReceive('get')
            ->with($queueServiceId);

        $locator = new ContainerLocator($container, 'vhost');

        $this->setExpectedException(MissingQueueException::class);
        $locator->getQueueByName('unknown_queue');
    }
}
