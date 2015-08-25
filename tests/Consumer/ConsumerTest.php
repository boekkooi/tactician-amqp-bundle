<?php
namespace Tests\Boekkooi\Bundle\AMQP\Consumer;

use Boekkooi\Bundle\AMQP\Consumer\Consumer;
use Boekkooi\Bundle\AMQP\QueueLocator\QueueLocator;
use Boekkooi\Bundle\AMQP\LazyChannel;
use Boekkooi\Bundle\AMQP\LazyConnection;
use Boekkooi\Bundle\AMQP\LazyQueue;
use Mockery;

class ConsumerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LazyConnection|Mockery\MockInterface
     */
    private $connection;
    /**
     * @var LazyChannel|Mockery\MockInterface
     */
    private $channel;
    /**
     * @var QueueLocator|Mockery\MockInterface
     */
    private $queueLocator;
    /**
     * @var Consumer
     */
    private $consumer;

    protected function setUp()
    {
        $this->connection = Mockery::mock(LazyConnection::class);
        $this->channel = Mockery::mock(LazyChannel::class);
        $this->queueLocator = Mockery::mock(QueueLocator::class);

        $this->consumer = new Consumer(
            $this->connection,
            $this->channel,
            $this->queueLocator
        );
    }

    /**
     * @test
     */
    public function it_should_consume_a_set_of_queues()
    {
        $callbackWasCalled = false;
        $callback = function () use (&$callbackWasCalled) {
            $callbackWasCalled = true;
        };

        $amqpConnection = $this->mockAmqpConnection();
        $amqpChannel = $this->mockAmqpChannel($amqpConnection);

        $amqpQueues = $this->mockQueueInitialize($amqpChannel, ['1', '2']);
        $this->mockQueueConsume($amqpQueues, $callback);
        $this->mockQueueCancel($amqpQueues);

        $this->consumer->consume(['1', '2'], $callback);

        $this->assertTrue($callbackWasCalled);
    }

    /**
     * @test
     */
    public function it_should_cancel_queues_when_callback_throw_a_exception()
    {
        $callback = function () use (&$callbackWasCalled) {
            throw new \RuntimeException('callback error');
        };

        $amqpConnection = $this->mockAmqpConnection();
        $amqpChannel = $this->mockAmqpChannel($amqpConnection);

        $amqpQueues = $this->mockQueueInitialize($amqpChannel, ['queue']);
        $this->mockQueueConsume($amqpQueues, $callback);
        $this->mockQueueCancel($amqpQueues);

        $this->setExpectedException(\RuntimeException::class, 'callback error');
        $this->consumer->consume(['queue'], $callback);
    }

    /**
     * @test
     */
    public function it_should_not_cancel_queues_or_throw_a_exception_when_a_timeout_occured()
    {
        $callback = function () use (&$callbackWasCalled) {
            throw new \AMQPQueueException('Consumer timeout exceed');
        };

        $amqpConnection = $this->mockAmqpConnection();
        $amqpChannel = $this->mockAmqpChannel($amqpConnection);

        $amqpQueues = $this->mockQueueInitialize($amqpChannel, ['queue']);
        $this->mockQueueConsume($amqpQueues, $callback);
        $this->mockQueueCancel($amqpQueues, false);

        $this->consumer->consume(['queue'], $callback);
    }

    /**
     * @test
     */
    public function it_should_not_cancel_queues_when_a_amqp_queue_exception_occured()
    {
        $callback = function () use (&$callbackWasCalled) {
            throw new \AMQPQueueException('some error');
        };

        $amqpConnection = $this->mockAmqpConnection();
        $amqpChannel = $this->mockAmqpChannel($amqpConnection);

        $amqpQueues = $this->mockQueueInitialize($amqpChannel, ['queue']);
        $this->mockQueueConsume($amqpQueues, $callback);
        $this->mockQueueCancel($amqpQueues, false);

        $this->setExpectedException(\AMQPQueueException::class, 'some error');
        $this->consumer->consume(['queue'], $callback);
    }

    /**
     * @test
     */
    public function it_should_not_cancel_queues_when_a_amqp_exception_occured()
    {
        $callback = function () use (&$callbackWasCalled) {
            throw new \AMQPException('some error');
        };

        $amqpConnection = $this->mockAmqpConnection();
        $amqpChannel = $this->mockAmqpChannel($amqpConnection);

        $amqpQueues = $this->mockQueueInitialize($amqpChannel, ['queue']);
        $this->mockQueueConsume($amqpQueues, $callback);
        $this->mockQueueCancel($amqpQueues, false);

        $this->setExpectedException(\AMQPException::class, 'some error');
        $this->consumer->consume(['queue'], $callback);
    }

    private function mockQueueInitialize($amqpChannel, array $queueNames)
    {
        $queues = [];
        foreach ($queueNames as $queueName) {
            $amqpQueue = Mockery::mock(\AMQPQueue::class);

            $queue = Mockery::mock(LazyQueue::class);
            $queue
                ->shouldReceive('create')
                ->once()
                ->with($amqpChannel)
                ->andReturn($amqpQueue);

            $this->queueLocator
                ->shouldReceive('getQueueByName')
                ->once()
                ->with($queueName)
                ->andReturn($queue);

            $queues[] = $amqpQueue;
        }

        return $queues;
    }

    /**
     * @param Mockery\MockInterface[] $queueMocks
     * @param callable $expectedCallback
     */
    private function mockQueueConsume(array $queueMocks, callable $expectedCallback)
    {
        /** @var Mockery\MockInterface $activeQueue */
        $activeQueue = array_pop($queueMocks);
        $activeQueue
            ->shouldReceive('consume')
            ->once()
            ->andReturnUsing(function (callable $callback) use ($expectedCallback, $queueMocks) {
                \PHPUnit_Framework_Assert::assertSame($expectedCallback, $callback);
                foreach ($queueMocks as $queueMock) {
                    $queueMock->shouldHaveReceived('consume');
                }

                $callback();
            });

        foreach ($queueMocks as $queueMock) {
            $queueMock
                ->shouldReceive('consume')
                ->once()
                ->with(null);
        }
    }

    /**
     * @param Mockery\MockInterface[] $queueMocks
     */
    private function mockQueueCancel(array $queueMocks, $shouldBeCalled = true)
    {
        foreach ($queueMocks as $queueMock) {
            if ($shouldBeCalled) {
                $queueMock
                    ->shouldReceive('cancel')
                    ->once()
                    ->withNoArgs();
            } else {
                $queueMock
                    ->shouldNotReceive('cancel');
            }
        }
    }

    /**
     * @return Mockery\MockInterface
     */
    private function mockAmqpConnection()
    {
        $amqpConnection = Mockery::mock(\AMQPConnection::class);
        $amqpConnection
            ->shouldReceive('isConnected')
            ->once()
            ->withNoArgs()
            ->andReturn(true);
        $amqpConnection
            ->shouldReceive('disconnect')
            ->once()
            ->withNoArgs();
        $this->connection
            ->shouldReceive('create')
            ->once()
            ->andReturn($amqpConnection);
        return $amqpConnection;
    }

    /**
     * @param $amqpConnection
     * @return Mockery\MockInterface
     */
    private function mockAmqpChannel($amqpConnection)
    {
        $amqpChannel = Mockery::mock(\AMQPChannel::class);
        $this->channel
            ->shouldReceive('create')
            ->with($amqpConnection)
            ->andReturn($amqpChannel);
        return $amqpChannel;
    }
}
