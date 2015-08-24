<?php
namespace Tests\Boekkooi\Bundle\AMQP\Transformer;

use Boekkooi\Bundle\AMQP\CommandConfiguration;
use Boekkooi\Bundle\AMQP\CommandMessage;
use Boekkooi\Bundle\AMQP\Exception\CommandTransformationException;
use Boekkooi\Bundle\AMQP\Transformer\CommandTransformer;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Mockery;

class CommandTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_should_transform_a_known_command()
    {
        $command = new \stdClass();
        $commandConfiguration = new CommandConfiguration(
            \stdClass::class,
            '/',
            'exchange',
            'key',
            AMQP_MANDATORY,
            ['headers' => ['my' => 'header']]
        );

        /** @var Mockery\MockInterface|SerializerInterface $serializer */
        $serializer = Mockery::mock(SerializerInterface::class);
        $serializer
            ->shouldReceive('serialize')
            ->atLeast()->once()
            ->with($command, 'json')
            ->andReturn('transfomed');

        $transformer = new CommandTransformer($serializer, 'json');
        $transformer->registerCommand($commandConfiguration);

        $message = $transformer->transformCommandToMessage($command);

        $this->assertInstanceOf(CommandMessage::class, $message);
        $this->assertSame('transfomed', $message->getMessage());
        $this->assertSame('key', $message->getRoutingKey());
        $this->assertSame(AMQP_MANDATORY, $message->getFlags());
        $this->assertSame('/', $message->getVHost());
        $this->assertSame('exchange', $message->getExchange());
        $this->assertEquals(
            [
                'content_type' => 'application/json',
                'headers' => [
                    'my' => 'header',
                    'x-symfony-command' => \stdClass::class
                ]
            ],
            $message->getAttributes()
        );
    }

    /**
     * @test
     */
    public function it_should_fail_if_the_serializer_has_no_format_support()
    {
        $this->setExpectedException(CommandTransformationException::class);

        /** @var Mockery\MockInterface|EncoderInterface|SerializerInterface $serializer */
        $serializer = Mockery::mock(SerializerInterface::class, EncoderInterface::class);
        $serializer
            ->shouldReceive('supportsEncoding')
            ->atLeast()->once()
            ->with('json')
            ->andReturn(false);

        $transformer = new CommandTransformer($serializer, 'json');
        $transformer->registerCommand(new CommandConfiguration(\stdClass::class, '/', 'e'));
        $transformer->transformCommandToMessage(new \stdClass());
    }

    /**
     * @test
     * @dataProvider provideInvalidCommands
     */
    public function it_should_fail_to_transform_for_invalid_or_unknown_commands($badCommand)
    {
        $this->setExpectedException(CommandTransformationException::class);

        /** @var Mockery\MockInterface|SerializerInterface $serializer */
        $serializer = Mockery::mock(SerializerInterface::class);

        $transformer = new CommandTransformer($serializer, 'json');
        $transformer->transformCommandToMessage($badCommand);
    }

    public function provideInvalidCommands()
    {
        return [
            [ [ ] ],
            [ new \stdClass() ]
        ];
    }
}
