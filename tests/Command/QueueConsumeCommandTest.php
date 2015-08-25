<?php
namespace Tests\Boekkooi\Bundle\AMQP\Command;

use Boekkooi\Bundle\AMQP\Command\QueueConsumeCommand;
use Boekkooi\Bundle\AMQP\Consumer\Consumer;
use Boekkooi\Bundle\AMQP\DependencyInjection\BoekkooiAMQPExtension;
use Boekkooi\Tactician\AMQP\Command;
use League\Tactician\CommandBus;
use Mockery;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class QueueConsumeCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerInterface|Mockery\MockInterface
     */
    private $container;

    /**
     * @var CommandBus|Mockery\MockInterface
     */
    private $commandBus;

    /**
     * @var QueueConsumeCommand
     */
    private $command;

    /**
     * @var CommandTester
     */
    private $commandTester;

    public function setUp()
    {
        $this->commandBus = Mockery::mock(CommandBus::class);

        $this->container = Mockery::mock(ContainerInterface::class);
        $this->container
            ->shouldReceive('get')
            ->with('boekkooi.amqp.consume_command_bus')
            ->andReturn($this->commandBus);

        $this->command = new QueueConsumeCommand($this->commandBus, $this->container);
        $this->command->setContainer($this->container);

        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * @test
     */
    public function it_should_process_a_single_queue()
    {
        $this->commandBus
            ->shouldReceive('handle')
            ->with(Mockery::type(Command::class))
            ->times(5);

        $consumer = $this->mockConsumerWithConsume(['my_queue']);

        $this->container
            ->shouldReceive('get')
            ->atLeast()->once()
            ->with(sprintf(BoekkooiAMQPExtension::SERVICE_VHOST_CONSUMER_ID, 'main'))
            ->andReturn($consumer);

        $statusCode = $this->commandTester->execute(
            array(
                '--amount' => 5,
                'vhost'    => 'main',
                'queues'   => 'my_queue',
            )
        );

        $this->assertSame(0, $statusCode);
    }

    /**
     * @test
     */
    public function it_should_fail_if_a_queue_cannot_be_found()
    {
        $this->setExpectedException(ServiceNotFoundException::class);

        $this->commandBus
            ->shouldNotReceive('handle')
            ->withAnyArgs();

        $this->container
            ->shouldReceive('get')
            ->with(sprintf(BoekkooiAMQPExtension::SERVICE_VHOST_CONSUMER_ID, '/', 'unknown'))
            ->andThrow(ServiceNotFoundException::class);

        $this->commandTester->execute(
            array(
                'vhost'    => '/',
                'queues'   => 'unknown',
            )
        );

        $this->assertSame(1, $this->commandTester->getStatusCode());
    }

    /**
     *
     * @test
     */
    public function it_should_fail_when_the_command_handler_throws_a_exception()
    {
        $this->setExpectedException(\RuntimeException::class);

        $consumer = $this->mockConsumerWithConsume(['queue']);

        $this->commandBus
            ->shouldReceive('handle')
            ->with(Mockery::type(Command::class))
            ->andThrow(\RuntimeException::class);

        $this->container
            ->shouldReceive('get')
            ->with(sprintf(BoekkooiAMQPExtension::SERVICE_VHOST_CONSUMER_ID, '/', 'queue'))
            ->andReturn($consumer);

        $this->commandTester->execute(
            array(
                'vhost'    => '/',
                'queues'   => 'queue',
            )
        );

        $this->assertSame(1, $this->commandTester->getStatusCode());
    }

    /**
     * @return Mockery\MockInterface
     */
    protected function mockConsumerWithConsume($expectedQueues)
    {
        $consumer = Mockery::mock(Consumer::class);
        $consumer
            ->shouldReceive('consume')
            ->once()
            ->andReturnUsing(
                function ($queues, callable $callback) use ($expectedQueues) {
                    \PHPUnit_Framework_Assert::assertEquals($expectedQueues, $queues);

                    do {
                        $envelope = Mockery::mock(\AMQPEnvelope::class);
                        $queue = Mockery::mock(\AMQPQueue::class);
                    } while (($callback($envelope, $queue)));
                }
            );
        return $consumer;
    }
}
