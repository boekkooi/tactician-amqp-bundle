<?php
namespace Tests\Boekkooi\Bundle\AMQP\Transformer;

use Boekkooi\Bundle\AMQP\Exception\EnvelopeTransformationException;
use Boekkooi\Bundle\AMQP\Transformer\EnvelopeTransformer;
use Symfony\Component\Serializer\SerializerInterface;
use Mockery;

class EnvelopeTransformerTest extends \PHPUnit_Framework_TestCase
{
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

        /** @var SerializerInterface|Mockery\MockInterface $serializer */
        $serializer = Mockery::mock(SerializerInterface::class);
        $serializer
            ->shouldReceive('deserialize')
            ->atLeast()->once()
            ->with('content', 'stdClass', 'json')
            ->andReturn('transformed');

        $transformer = new EnvelopeTransformer($serializer, 'xml');

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

        /** @var SerializerInterface|Mockery\MockInterface $serializer */
        $serializer = Mockery::mock(SerializerInterface::class);
        $serializer
            ->shouldReceive('deserialize')
            ->atLeast()->once()
            ->with('content', 'stdClass', 'xml')
            ->andReturn('transformed');

        $transformer = new EnvelopeTransformer($serializer, 'xml');

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

        /** @var SerializerInterface|Mockery\MockInterface $serializer */
        $serializer = Mockery::mock(SerializerInterface::class);
        $serializer->shouldNotReceive('deserialize');

        $transformer = new EnvelopeTransformer($serializer, 'xml');
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

        /** @var SerializerInterface|Mockery\MockInterface $serializer */
        $serializer = Mockery::mock(SerializerInterface::class);
        $serializer->shouldNotReceive('deserialize');

        $transformer = new EnvelopeTransformer($serializer, 'xml');
        $transformer->transformEnvelopeToCommand($envelope);
    }
}
