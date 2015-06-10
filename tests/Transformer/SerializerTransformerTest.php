<?php
namespace Tests\Boekkooi\Bundle\AMQP\Transformer;

use Boekkooi\Bundle\AMQP\AMQPMessage;
use Boekkooi\Bundle\AMQP\Exception\CommandTransformationException;
use Boekkooi\Bundle\AMQP\Exception\EnvelopeTransformationException;
use Boekkooi\Bundle\AMQP\Exception\InvalidArgumentException;
use Boekkooi\Bundle\AMQP\Transformer\SerializerTransformer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Mockery;

/**
 * @author Warnar Boekkooi <warnar@boekkooi.net>
 */
class SerializerTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_should_transform_a_known_command()
    {
        $command = new \stdClass();

        $serializer = $this->mockPlainSerializer();
        $serializer
            ->shouldReceive('serialize')
            ->with($command, 'json')
            ->andReturn('transfomed');

        $transformer = new SerializerTransformer($serializer, 'json');
        $transformer->addCommand(\stdClass::class, [
            'vhost' => '/',
            'exchange' => 'exchange',
            'routing_key' => 'key',
            'flags' => AMQP_MANDATORY,
            'attributes' => ['headers' => ['my' => 'header']]
        ]);

        $message = $transformer->transformCommandToMessage($command);

        $this->assertInstanceOf(AMQPMessage::class, $message);
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

        $serializer = new Serializer();

        $transformer = new SerializerTransformer($serializer, 'json');
        $transformer->addCommand(\stdClass::class, [ 'vhost' => '/', 'exchange' => 'e' ]);
        $transformer->transformCommandToMessage(new \stdClass());
    }

    /**
     * @test
     * @dataProvider provideInvalidCommands
     */
    public function it_should_fail_to_transform_for_invalid_or_unknown_commands($badCommand)
    {
        $this->setExpectedException(CommandTransformationException::class);

        $transformer = new SerializerTransformer($this->mockPlainSerializer(), 'json');
        $transformer->transformCommandToMessage($badCommand);
    }

    public function provideInvalidCommands()
    {
        return [
            [ [ ] ],
            [ new \stdClass() ]
        ];
    }

    /**
     * @test
     */
    public function it_should_register_amqp_command_metadata()
    {
        $transformer = new SerializerTransformer($this->mockPlainSerializer(), 'json');

        $transformer->addCommands([
            '\stdClass' => [
                'vhost' => '/',
                'exchange' => 'exchange'
            ],
            'A\Command' => [
                'vhost' => '/a',
                'exchange' => 'command',
                'routing_key' => 'a_command',
                'flags' => AMQP_MANDATORY,
                'attributes' => ['headers' => ['some' => 'header']]
            ]
        ]);

        $this->assertEquals(
            [
                'stdClass' => [
                    'vhost' => '/',
                    'exchange' => 'exchange',
                    'routing_key' => null,
                    'flags' => AMQP_NOPARAM,
                    'attributes' => []
                ],
                'A\Command' => [
                    'vhost' => '/a',
                    'exchange' => 'command',
                    'routing_key' => 'a_command',
                    'flags' => AMQP_MANDATORY,
                    'attributes' => ['headers' => ['some' => 'header']]
                ]
            ],
            $transformer->getCommands()
        );
    }

    /**
     * @test
     * @dataProvider provideIncompleteCommandMetadata
     */
    public function it_should_fail_when_register_amqp_command_metadata_is_incomplete(array $metadata)
    {
        $this->setExpectedException(InvalidArgumentException::class);

        $transformer = new SerializerTransformer($this->mockPlainSerializer(), 'json');
        $transformer->addCommand('stdClass', $metadata);
    }

    public function provideIncompleteCommandMetadata()
    {
        return [
            [ [] ],
            [ [ 'vhost' => '/' ] ],
            [ [ 'vhost' => null ] ],
            [ [ 'vhost' => false ] ],
            [ [ 'vhost' => '/', 'exchange' => null ] ],
            [ [ 'vhost' => '/', 'exchange' => false ] ],
            [ [ 'vhost' => '/', 'exchange' => 'e', 'routing_key' => [] ] ],
            [ [ 'vhost' => '/', 'exchange' => 'e', 'flags' => 'string' ] ],
            [ [ 'vhost' => '/', 'exchange' => 'e', 'attributes' => 'string' ] ]
        ];
    }

    /**
     * @test
     */
    public function it_should_transform_a_envelope_to_a_command()
    {
        /** @var \AMQPEnvelope|Mockery\MockInterface $envelope */
        $envelope = Mockery::mock(\AMQPEnvelope::class);
        $envelope->shouldReceive('getContentType')->andReturn('application/json');
        $envelope->shouldReceive('getHeader')->with('x-symfony-command')->andReturn('stdClass');
        $envelope->shouldReceive('getBody')->andReturn('content');

        $serializer = $this->mockPlainSerializer();
        $serializer
            ->shouldReceive('deserialize')
            ->with('content', 'stdClass', 'json')
            ->andReturn('transformed');

        $transformer = new SerializerTransformer($serializer, 'xml');

        $this->assertEquals(
            'transformed',
            $transformer->transformEnvelopeToCommand($envelope)
        );
    }

    /**
     * @test
     */
    public function it_should_fallback_to_the_internal_type_if_a_envelope_has_no_content_type()
    {
        /** @var \AMQPEnvelope|Mockery\MockInterface $envelope */
        $envelope = Mockery::mock(\AMQPEnvelope::class);
        $envelope->shouldReceive('getContentType')->andReturn('');
        $envelope->shouldReceive('getHeader')->with('x-symfony-command')->andReturn('stdClass');
        $envelope->shouldReceive('getBody')->andReturn('content');

        $serializer = $this->mockPlainSerializer();
        $serializer
            ->shouldReceive('deserialize')
            ->with('content', 'stdClass', 'xml')
            ->andReturn('transformed');

        $transformer = new SerializerTransformer($serializer, 'xml');

        $this->assertEquals(
            'transformed',
            $transformer->transformEnvelopeToCommand($envelope)
        );
    }

    /**
     * @test
     */
    public function it_should_fail_to_transform_a_envelope_when_there_is_no_header()
    {
        $this->setExpectedException(EnvelopeTransformationException::class);

        /** @var \AMQPEnvelope|Mockery\MockInterface $envelope */
        $envelope = Mockery::mock(\AMQPEnvelope::class);
        $envelope->shouldReceive('getContentType')->andReturn('application/json');
        $envelope->shouldReceive('getHeader')->with('x-symfony-command')->andReturn(false);
        $envelope->shouldReceive('getBody')->andReturn('content');

        $serializer = $this->mockPlainSerializer();
        $serializer->shouldNotReceive('deserialize');

        $transformer = new SerializerTransformer($serializer, 'xml');
        $transformer->transformEnvelopeToCommand($envelope);
    }

    /**
     * @test
     */
    public function it_should_fail_to_transform_a_envelope_when_the_class_header_is_not_existing()
    {
        $this->setExpectedException(EnvelopeTransformationException::class);

        /** @var \AMQPEnvelope|Mockery\MockInterface $envelope */
        $envelope = Mockery::mock(\AMQPEnvelope::class);
        $envelope->shouldReceive('getContentType')->andReturn('application/json');
        $envelope->shouldReceive('getHeader')->with('x-symfony-command')->andReturn('no\class\like\this\i\hope');
        $envelope->shouldReceive('getBody')->andReturn('content');

        $serializer = $this->mockPlainSerializer();
        $serializer->shouldNotReceive('deserialize');

        $transformer = new SerializerTransformer($serializer, 'xml');
        $transformer->transformEnvelopeToCommand($envelope);
    }

    /**
     * @return SerializerInterface|Mockery\MockInterface
     */
    private function mockPlainSerializer()
    {
        return Mockery::mock(SerializerInterface::class);
    }
}
