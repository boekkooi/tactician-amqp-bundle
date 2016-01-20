<?php
namespace Tests\Boekkooi\Bundle\AMQP\DependencyInjection;

use Boekkooi\Bundle\AMQP\CommandConfiguration;
use Boekkooi\Bundle\AMQP\DependencyInjection\BoekkooiAMQPExtension;
use Boekkooi\Bundle\AMQP\Exception\InvalidConfigurationException;
use League\Tactician\Bundle\DependencyInjection\TacticianExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\DefinitionHasMethodCallConstraint;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\Compiler\ResolveDefinitionTemplatesPass;
use Symfony\Component\DependencyInjection\Dumper;
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
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('boekkooi.amqp.tactician.exchange_locator', 0, new Reference('service_container'));

        $this->assertContainerBuilderHasService('boekkooi.amqp.tactician.publisher_locator');
        $this->assertContainerBuilderHasService('boekkooi.amqp.tactician.publisher.basic');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('boekkooi.amqp.tactician.publisher.basic', 0, new Reference('boekkooi.amqp.tactician.exchange_locator'));
        $this->assertContainerBuilderHasService('boekkooi.amqp.tactician.publisher.rpc');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('boekkooi.amqp.tactician.publisher.rpc', 0, new Reference('boekkooi.amqp.tactician.exchange_locator'));

        $this->assertContainerBuilderHasService('boekkooi.amqp.tactician.serializer');
        $this->assertContainerBuilderHasService('boekkooi.amqp.tactician.envelope_transformer');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('boekkooi.amqp.tactician.envelope_transformer', 0, new Reference('boekkooi.amqp.tactician.serializer'));
        $this->assertContainerBuilderHasService('boekkooi.amqp.tactician.command_transformer');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('boekkooi.amqp.tactician.command_transformer', 0, new Reference('boekkooi.amqp.tactician.serializer'));
        $this->assertContainerBuilderHasService('boekkooi.amqp.tactician.response_transformer');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('boekkooi.amqp.tactician.response_transformer', 0, new Reference('boekkooi.amqp.tactician.serializer'));

        $this->assertContainerBuilderHasParameter('boekkooi.amqp.tactician.serializer.format', 'json');
        $this->assertContainerBuilderHasParameter('boekkooi.amqp.tactician.serializer.service', 'boekkooi.amqp.tactician.serializer');

        $this->assertContainerBuilderHasService('boekkooi.amqp.middleware.publish');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('boekkooi.amqp.middleware.publish', 0, new Reference('boekkooi.amqp.tactician.publisher_locator'));
        $this->assertContainerBuilderHasService('boekkooi.amqp.middleware.consume');
        $this->assertContainerBuilderHasService('boekkooi.amqp.middleware.remote_response');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('boekkooi.amqp.middleware.remote_response', 0, new Reference('boekkooi.amqp.tactician.response_transformer'));
        $this->assertContainerBuilderHasService('boekkooi.amqp.middleware.command_transformer');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('boekkooi.amqp.middleware.command_transformer', 0, new Reference('boekkooi.amqp.tactician.command_transformer'));
        $this->assertContainerBuilderHasService('boekkooi.amqp.middleware.envelope_transformer');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('boekkooi.amqp.middleware.envelope_transformer', 0, new Reference('boekkooi.amqp.tactician.envelope_transformer'));

        $this->assertContainerBuilderHasService('boekkooi.amqp.consume_command_bus');
    }

    public function testBasicConfig()
    {
        $this->load([
            'connections' => [
                'default' => [],
            ],
            'vhosts' => [
                'root' => [
                    'path' => '/',
                    'exchanges' => [
                        'my_exchange' => [
                            'type' => 'direct'
                        ],
                        'second_exchange' => [
                            'type' => 'fanout',
                            'arguments' => [ 'key' => 'val' ],
                            'passive' => true,
                            'durable' => false
                        ]
                    ],
                    'queues' => [
                        'test' => [
                            'binds' => [
                                'my_exchange' => []
                            ]
                        ],
                        'test2' => [
                            'arguments' => [ 'key' => 'val' ],
                            'passive' => false,
                            'durable' => false,
                            'exclusive' => false,
                            'auto_delete' => false,
                            'binds' => [
                                'second_exchange' => []
                            ]
                        ]
                    ]
                ]
            ],
            'commands' => [
                'my\\Command' => [
                    'vhost' => 'root',
                    'exchange' => 'my_exchange'
                ],
            ]
        ]);

        $connectionId = sprintf(BoekkooiAMQPExtension::SERVICE_VHOST_CONNECTION_ID, 'root');
        $this->assertContainerBuilderHasService($connectionId);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($connectionId, 0, 'localhost');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($connectionId, 1, 5672);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($connectionId, 2, '/');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($connectionId, 3, 'guest');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($connectionId, 4, 'guest');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($connectionId, 5, 10);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($connectionId, 6, 10);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($connectionId, 7, 10);

        $vhostListId = BoekkooiAMQPExtension::PARAMETER_VHOST_LIST;
        $this->assertContainerBuilderHasParameter($vhostListId, ['root']);

        $exchangeId = sprintf(BoekkooiAMQPExtension::SERVICE_VHOST_EXCHANGE_ID, 'root', 'my_exchange');
        $this->assertContainerBuilderHasService($exchangeId);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($exchangeId, 0, 'my_exchange');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($exchangeId, 1, AMQP_EX_TYPE_DIRECT);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($exchangeId, 2, false);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($exchangeId, 3, true);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($exchangeId, 4, []);

        $exchangeId = sprintf(BoekkooiAMQPExtension::SERVICE_VHOST_EXCHANGE_ID, 'root', 'second_exchange');
        $this->assertContainerBuilderHasService($exchangeId);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($exchangeId, 0, 'second_exchange');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($exchangeId, 1, AMQP_EX_TYPE_FANOUT);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($exchangeId, 2, true);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($exchangeId, 3, false);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($exchangeId, 4, ['key' => 'val']);

        $exchangeListId = sprintf(BoekkooiAMQPExtension::PARAMETER_VHOST_EXCHANGE_LIST, 'root');
        $this->assertContainerBuilderHasParameter($exchangeListId, ['my_exchange', 'second_exchange']);

        $queueId = sprintf(BoekkooiAMQPExtension::SERVICE_VHOST_QUEUE_ID, 'root', 'test');
        $this->assertContainerBuilderHasService($queueId);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($queueId, 0, 'test');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($queueId, 1, false);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($queueId, 2, true);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($queueId, 3, false);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($queueId, 4, false);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($queueId, 5, []);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($queueId, 6, [
            'my_exchange' => [
                'routing_key' => null,
                'arguments' => []
            ]
        ]);

        $queueId = sprintf(BoekkooiAMQPExtension::SERVICE_VHOST_QUEUE_ID, 'root', 'test2');
        $this->assertContainerBuilderHasService($queueId);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($queueId, 0, 'test2');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($queueId, 1, false);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($queueId, 2, false);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($queueId, 3, false);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($queueId, 4, false);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($queueId, 5, ['key' => 'val']);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($queueId, 6, [
            'second_exchange' => [
                'routing_key' => null,
                'arguments' => []
            ]
        ]);

        $queueListId = sprintf(BoekkooiAMQPExtension::PARAMETER_VHOST_QUEUE_LIST, 'root');
        $this->assertContainerBuilderHasParameter($queueListId, ['test', 'test2']);

        $transformerId = 'boekkooi.amqp.tactician.command_transformer';
        $this->assertContainerBuilderHasService($transformerId);

        // Check that the command config is correctly set
        $transformerDef = $this->container->getDefinition('boekkooi.amqp.tactician.command_transformer');
        $commandConfigId = (string)$transformerDef->getMethodCalls()[0][1][0];
        $commandConfigDef = $this->container->getDefinition($commandConfigId);
        $this->assertEquals(CommandConfiguration::class, $commandConfigDef->getClass());
        $this->assertEquals(['my\\Command', 'root', 'my_exchange', null, AMQP_MANDATORY, []], $commandConfigDef->getArguments());

        $transformerMiddlewareId = 'boekkooi.amqp.middleware.command_transformer';
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall($transformerMiddlewareId, 'addSupportedCommand', [
            'my\\Command'
        ]);

        //TODO check locator
        $this->assertContainerCanBeDumped();
    }

    public function testMissingVHostConnection()
    {
        $this->setExpectedException(InvalidConfigurationException::class);

        $this->load([
            'vhosts' => [
                'root' => [
                    'path' => '/',
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
                    'vhost' => 'root',
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
                'root' => ['path' => '/']
            ],
            'commands' => [
                'my\\Command' => [
                    'vhost' => 'root',
                    'exchange' => 'my_exchange'
                ],
            ]
        ]);
    }

    /**
     * @dataProvider provideDumperClasses
     */
    public function testContainerCanBeDumped($dumperClass)
    {
        $this->load();

        // Load tactician
        $tacticianExt = new TacticianExtension();
        $tacticianExt->load([], $this->container);

        /** @var Dumper\Dumper $dumper */
        $dumper = new $dumperClass($this->container);
        $this->assertInstanceOf(Dumper\Dumper::class, $dumper);

        $dumper->dump();
    }

    public function provideDumperClasses()
    {
        return [
            [ Dumper\XmlDumper::class ],
            [ Dumper\PhpDumper::class ],
            [ Dumper\GraphvizDumper::class ],
            [ Dumper\YamlDumper::class ],
        ];
    }

    /**
     * Assert that the ContainerBuilder for this test has a service definition with the given id, which has no method
     * call to the given method with the given arguments.
     *
     * @param string $serviceId
     * @param string $method
     * @param array $arguments
     */
    protected function assertContainerBuilderHasServiceDefinitionWithoutMethodCall(
        $serviceId,
        $method,
        array $arguments = array()
    ) {
        $definition = $this->container->findDefinition($serviceId);

        self::assertThat($definition, new \PHPUnit_Framework_Constraint_Not(new DefinitionHasMethodCallConstraint($method, $arguments)));
    }

    /**
     * Assert that the current container can be dumped.
     */
    private function assertContainerCanBeDumped()
    {
        // Load tactician
        $tacticianExt = new TacticianExtension();
        $tacticianExt->load([], $this->container);

        // Compile child definitions
        $this->container->getCompilerPassConfig()->setOptimizationPasses(array(
            new ResolveDefinitionTemplatesPass()
        ));
        $this->container->compile();

        $dumperCalls = $this->provideDumperClasses();
        foreach ($dumperCalls as $dumperCall) {
            $dumperClass = $dumperCall[0];

            /** @var Dumper\Dumper $dumper */
            $dumper = new $dumperClass($this->container);
            $this->assertInstanceOf(Dumper\Dumper::class, $dumper);

            $dumper->dump();
        }
    }
}
