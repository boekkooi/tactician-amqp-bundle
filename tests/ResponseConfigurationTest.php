<?php
namespace Tests\Boekkooi\Bundle\AMQP;

use Boekkooi\Bundle\AMQP\ResponseConfiguration;
use Boekkooi\Bundle\AMQP\Exception\ResponseConfigurationException;

class ResponseConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_should_be_constructed_with_defaults()
    {
        $config = new ResponseConfiguration(__CLASS__);

        $this->assertEquals($config->getClass(), __CLASS__);
        $this->assertEquals($config->getFlags(), AMQP_NOPARAM);
        $this->assertEquals($config->getAttributes(), []);
    }

    /**
     * @test
     */
    public function it_should_be_constructed()
    {
        $config = new ResponseConfiguration(
            '\\stdClass',
            AMQP_MANDATORY,
            ['headers' => ['some' => 'header']]
        );

        $this->assertEquals($config->getClass(), 'stdClass');
        $this->assertEquals($config->getFlags(), AMQP_MANDATORY);
        $this->assertEquals($config->getAttributes(), ['headers' => ['some' => 'header']]);
    }

    /**
     * @test
     * @dataProvider provideIncompleteCommandMetadata
     */
    public function it_should_fail_when_invalid_data_is_provided($class, $flags = AMQP_NOPARAM, $attributes = [])
    {
        $this->setExpectedException(ResponseConfigurationException::class);

        new ResponseConfiguration($class, $flags, $attributes);
    }

    public function provideIncompleteCommandMetadata()
    {
        return [
            // flags
            [ '/stdClass', 'flag' ],
        ];
    }
}
