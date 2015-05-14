<?php
namespace Tests\Boekkooi\Bundle\AMQP\Middleware;

use Boekkooi\Bundle\AMQP\Exception\CommandTransformationException;
use Boekkooi\Bundle\AMQP\Middleware\CommandTransformerMiddleware;
use Boekkooi\Bundle\AMQP\Transformer\CommandTransformer;
use Boekkooi\Tactician\AMQP\Message;
use Mockery;

class CommandTransformerMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CommandTransformer|Mockery\MockInterface
     */
    private $transformer;

    /**
     * @var CommandTransformerMiddleware
     */
    private $middleware;

    public function setUp()
    {
        $this->transformer = Mockery::mock(CommandTransformer::class);
        $this->middleware = new CommandTransformerMiddleware($this->transformer);
    }

    /**
     * @test
     */
    public function it_should_transform_a_registered_command()
    {
        $command = new \stdClass();
        $message = Mockery::mock(Message::class);

        $this->middleware->addSupportedCommand('\stdClass');

        $this->transformer
            ->shouldReceive('transformCommandToMessage')
            ->with($command)
            ->andReturn($message);

        $this->execute($command, $message);
    }

    /**
     * @test
     */
    public function it_should_pass_trough_unknown_commands()
    {
        $this->transformer->shouldNotReceive('transformCommandToMessage');

        $command = new \stdClass();

        $this->execute($command, $command);
    }

    /**
     * @test
     */
    public function it_should_fail_when_no_message_is_returned()
    {
        $this->setExpectedException(CommandTransformationException::class);

        $command = new \stdClass();

        $this->middleware->addSupportedCommand('\stdClass');

        $this->transformer
            ->shouldReceive('transformCommandToMessage')
            ->with($command)
            ->andReturn(null);

        $this->middleware->execute(
            $command,
            function () {
                throw new \LogicException('Next should not have been called!');
            }
        );
    }

    /**
     * @test
     */
    public function it_should_register_multiple_commands()
    {
        $this->middleware->addSupportedCommand('\stdClass');
        $this->middleware->addSupportedCommands(['Yet\Another\Command', 'Another\Command']);

        $this->assertEquals(
            ['stdClass', 'Yet\Another\Command', 'Another\Command'],
            $this->middleware->getSupportedCommands()
        );
    }

    private function execute($command, $expectedNextCommand)
    {
        $nextWasCalled = false;
        $this->middleware->execute(
            $command,
            function ($nextCommand) use ($expectedNextCommand, &$nextWasCalled) {
                \PHPUnit_Framework_Assert::assertSame($expectedNextCommand, $nextCommand);
                $nextWasCalled = true;
            }
        );

        if (!$nextWasCalled) {
            throw new \LogicException('Middleware should have called the next callable.');
        }
    }
}
