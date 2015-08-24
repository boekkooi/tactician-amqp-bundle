<?php
namespace Tests\Boekkooi\Bundle\AMQP;

use Boekkooi\Bundle\AMQP\CommandConfiguration;
use Boekkooi\Bundle\AMQP\Exception\CommandConfigurationException;

class CommandConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_should_be_constructed_with_defaults()
    {
        $config = new CommandConfiguration(
            __CLASS__,
            '/',
            'e'
        );

        $this->assertEquals($config->getClass(), __CLASS__);
        $this->assertEquals($config->getVhost(), '/');
        $this->assertEquals($config->getExchange(), 'e');
        $this->assertEquals($config->getRoutingKey(), null);
        $this->assertEquals($config->getFlags(), AMQP_NOPARAM);
        $this->assertEquals($config->getAttributes(), []);
    }

    /**
     * @test
     */
    public function it_should_be_constructed()
    {
        $config = new CommandConfiguration(
            '\stdClass',
            '/a',
            'command',
            'a_command',
            AMQP_MANDATORY,
            ['headers' => ['some' => 'header']]
        );

        $this->assertEquals($config->getClass(), 'stdClass');
        $this->assertEquals($config->getVhost(), '/a');
        $this->assertEquals($config->getExchange(), 'command');
        $this->assertEquals($config->getRoutingKey(), 'a_command');
        $this->assertEquals($config->getFlags(), AMQP_MANDATORY);
        $this->assertEquals($config->getAttributes(), ['headers' => ['some' => 'header']]);
    }

    /**
     * @test
     * @dataProvider provideIncompleteCommandMetadata
     */
    public function it_should_fail_when_invalid_data_is_provided($class, $vhost, $exchange, $routingKey = null, $flags = AMQP_NOPARAM, $attributes = [])
    {
        $this->setExpectedException(CommandConfigurationException::class);

        new CommandConfiguration($class, $vhost, $exchange, $routingKey, $flags, $attributes);
    }

    public function provideIncompleteCommandMetadata()
    {
        return [
            // vhost
            [ '/stdClass', null, 'exchange' ],
            [ '/stdClass', false, 'exchange' ],

            // Exchange
            [ '/stdClass', '/', null ],
            [ '/stdClass', '/', false ],

            // Routing key
            [ '/stdClass', '/', 'exchange', [] ],

            // flags
            [ '/stdClass', '/', 'exchange', null, 'flag' ],
        ];
    }
}
