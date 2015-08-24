<?php
namespace Tests\Boekkooi\Bundle\AMQP\Transformer;

use Boekkooi\Bundle\AMQP\ResponseConfiguration;
use Boekkooi\Bundle\AMQP\ResponseMessage;
use Boekkooi\Bundle\AMQP\Exception\CommandTransformationException;
use Boekkooi\Bundle\AMQP\Transformer\ResponseTransformer;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Mockery;

class ResponseTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_should_transform_a_known_response()
    {
        $response = new \stdClass();
        $responseConfiguration = new ResponseConfiguration(
            \stdClass::class,
            AMQP_MANDATORY,
            ['headers' => ['my' => 'header']]
        );

        /** @var Mockery\MockInterface|SerializerInterface $serializer */
        $serializer = Mockery::mock(SerializerInterface::class);
        $serializer
            ->shouldReceive('serialize')
            ->atLeast()->once()
            ->with($response, 'json')
            ->andReturn('transformed');

        $transformer = new ResponseTransformer($serializer, 'json');
        $transformer->registerResponse($responseConfiguration);

        $message = $transformer->transformCommandResponse($response);

        $this->assertInstanceOf(ResponseMessage::class, $message);
        $this->assertNull($message->getRoutingKey());
        $this->assertSame('transformed', $message->getMessage());
        $this->assertSame(AMQP_MANDATORY, $message->getFlags());
        $this->assertEquals(
            [
                'content_type' => 'application/json',
                'headers' => [
                    'my' => 'header',
                    'x-symfony-response' => \stdClass::class
                ]
            ],
            $message->getAttributes()
        );
    }

    /**
     * @test
     */
    public function it_should_transform_a_unknown_response()
    {
        $response = [ 'some data' ];

        /** @var Mockery\MockInterface|SerializerInterface $serializer */
        $serializer = Mockery::mock(SerializerInterface::class);
        $serializer
            ->shouldReceive('serialize')
            ->atLeast()->once()
            ->with($response, 'json')
            ->andReturn('transformed unknown');

        $transformer = new ResponseTransformer($serializer, 'json');
        $message = $transformer->transformCommandResponse($response);

        $this->assertInstanceOf(ResponseMessage::class, $message);
        $this->assertNull($message->getRoutingKey());
        $this->assertSame('transformed unknown', $message->getMessage());
        $this->assertSame(AMQP_NOPARAM, $message->getFlags());
        $this->assertEquals(
            [
                'content_type' => 'application/json'
            ],
            $message->getAttributes()
        );
    }
}
