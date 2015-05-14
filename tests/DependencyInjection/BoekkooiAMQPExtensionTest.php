<?php
namespace Tests\Boekkooi\Bundle\AMQP\DependencyInjection;

use Boekkooi\Bundle\AMQP\DependencyInjection\BoekkooiAMQPExtension;
use Boekkooi\Bundle\AMQP\Exception\InvalidConfigurationException;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\DependencyInjection\Reference;

class BoekkooiAMQPExtensionTest extends AbstractExtensionTestCase
{

    /**
     * {@inheritdoc}
     */
    protected function getContainerExtensions()
    {
        return [
            new BoekkooiAMQPExtension()
        ];
    }

    public function testDefaults()
    {
        $this->load();

        $this->assertContainerBuilderHasService('boekkooi.amqp.tactician.exchange_locator');
        $this->assertContainerBuilderHasService('boekkooi.amqp.tactician.publisher');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('boekkooi.amqp.tactician.publisher', 0, new Reference('boekkooi.amqp.tactician.exchange_locator'));
        $this->assertContainerBuilderHasService('boekkooi.amqp.tactician.exchange_locator');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('boekkooi.amqp.tactician.exchange_locator', 0, new Reference('service_container'));

        $this->assertContainerBuilderHasService('boekkooi.amqp.tactician.serializer');
        $this->assertContainerBuilderHasService('boekkooi.amqp.tactician.transformer');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('boekkooi.amqp.tactician.transformer', 0, new Reference('boekkooi.amqp.tactician.serializer'));

        $this->assertContainerBuilderHasService('boekkooi.amqp.middleware.publish');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('boekkooi.amqp.middleware.publish', 0, new Reference('boekkooi.amqp.tactician.publisher'));
        $this->assertContainerBuilderHasService('boekkooi.amqp.middleware.consume');
        $this->assertContainerBuilderHasService('boekkooi.amqp.middleware.command_transformer');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('boekkooi.amqp.middleware.command_transformer', 0, new Reference('boekkooi.amqp.tactician.transformer'));
        $this->assertContainerBuilderHasService('boekkooi.amqp.middleware.envelope_transformer');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('boekkooi.amqp.middleware.envelope_transformer', 0, new Reference('boekkooi.amqp.tactician.transformer'));

        $this->assertContainerBuilderHasService('boekkooi.amqp.command.handle');
    }

    public function testBasicConfig()
    {
        $this->load([
            'connections' => [
                'default' => [],
            ],
            'vhosts' => [
                '/' => [
                    'exchanges' => [
                        'my_exchange' => [
                            'type' => 'direct'
                        ]
                    ],
                    'queues' => [
                        'test' => [
                            'binds' => [
                                'my_exchange' => []
                            ]
                        ]
                    ]
                ]
            ],
            'commands' => [
                'my\\Command' => [
                    'vhost' => '/',
                    'exchange' => 'my_exchange'
                ],
            ]
        ]);

        $connectionId = sprintf(BoekkooiAMQPExtension::SERVICE_CONNECTION_ID, 'default');
        $this->assertContainerBuilderHasService($connectionId);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($connectionId, 0, [
            'host' => 'localhost',
            'port' => 5672,
            'read_timeout' => 10,
            'write_timeout' => 10,
            'connect_timeout' => 10,
            'login' => 'guest',
            'password' => 'guest'
        ]);

        $exchangeId = sprintf(BoekkooiAMQPExtension::SERVICE_VHOST_EXCHANGE_ID, '/', 'my_exchange');
        $this->assertContainerBuilderHasService($exchangeId);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall($exchangeId, 'setName', [ 'my_exchange' ]);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall($exchangeId, 'setArguments', [ [] ]);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall($exchangeId, 'setType', [ AMQP_EX_TYPE_DIRECT ]);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall($exchangeId, 'setFlags', [ AMQP_DURABLE ]);

        $queueId = sprintf(BoekkooiAMQPExtension::SERVICE_VHOST_QUEUE_ID, '/', 'test');
        $this->assertContainerBuilderHasService($queueId);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall($queueId, 'setName', [ 'test' ]);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall($queueId, 'setArguments', [ [] ]);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall($queueId, 'setFlags', [ AMQP_DURABLE ]);

        $queueBindsId = sprintf(BoekkooiAMQPExtension::PARAMETER_VHOST_QUEUE_BINDS, '/', 'test');
        $this->assertContainerBuilderHasParameter($queueBindsId, ['my_exchange' => [ 'routing_key' => null, 'arguments' => [] ]]);

        $transformerId = 'boekkooi.amqp.tactician.transformer';
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall($transformerId, 'addCommands', [[
            'my\\Command' => [
                'vhost' => '/',
                'exchange' => 'my_exchange',
                'routing_key' => null,
                'flags' => AMQP_MANDATORY,
                'attributes' => []
            ]
        ]]);

        $transformerMiddlewareId = 'boekkooi.amqp.middleware.command_transformer';
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall($transformerMiddlewareId, 'addSupportedCommand', [
            [ 'my\\Command' ]
        ]);
    }

    public function testMissingVHostConnection()
    {
        $this->setExpectedException(InvalidConfigurationException::class);

        $this->load([
            'vhosts' => [
                '/' => [
                    'connection' => 'none',
                ]
            ]
        ]);
    }

    public function testMissingVHostForCommand()
    {
        $this->setExpectedException(InvalidConfigurationException::class);

        $this->load([
            'commands' => [
                'my\\Command' => [
                    'vhost' => '/',
                    'exchange' => 'my_exchange'
                ],
            ]
        ]);
    }

    public function testMissingExchangeForCommand()
    {
        $this->setExpectedException(InvalidConfigurationException::class);

        $this->load([
            'connections' => [
                'default' => [],
            ],
            'vhosts' => [
                '/' => []
            ],
            'commands' => [
                'my\\Command' => [
                    'vhost' => '/',
                    'exchange' => 'my_exchange'
                ],
            ]
        ]);
    }
}
