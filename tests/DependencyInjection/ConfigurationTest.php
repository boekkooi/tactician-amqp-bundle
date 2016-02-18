<?php
namespace Tests\Boekkooi\Bundle\AMQP\DependencyInjection;

use Boekkooi\Bundle\AMQP\DependencyInjection\Configuration;
use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    use ConfigurationTestCaseTrait;

    /**
     * {@inheritdoc}
     */
    protected function getConfiguration()
    {
        return new Configuration();
    }

    public function testBlankConfiguration()
    {
        $this->assertProcessedConfigurationEquals(
            [],
            $this->defaultConfig()
        );
    }

    public function testConnectionDefaults()
    {
        $this->assertProcessedConfigurationEquals(
            [
                'boekkooi_amqp' => [
                    'connections' => [
                        'default' => []
                    ]
                ]
            ],
            array_merge($this->defaultConfig(), [
                'connections' => [
                    'default' => $this->defaultConnectionConfig()
                ]
            ])
        );
    }

    public function testConnectionTimeoutArray()
    {
        $this->assertProcessedConfigurationEquals(
            [
                'boekkooi_amqp' => [
                    'connections' => [
                        'custom' => [
                            'timeout' => [
                                'read' => 20,
                                'write' => 25,
                                'connect' => 30
                            ]
                        ]
                    ]
                ]
            ],
            array_merge($this->defaultConfig(), [
                'connections' => [
                    'custom' => array_merge($this->defaultConnectionConfig(), [
                        'timeout' => [
                            'read' => 20,
                            'write' => 25,
                            'connect' => 30
                        ]
                    ])
                ]
            ])
        );
    }

    public function testConnectionTimeoutInt()
    {
        $this->assertProcessedConfigurationEquals(
            [
                'boekkooi_amqp' => [
                    'connections' => [
                        'custom' => [
                            'timeout' => 40
                        ]
                    ]
                ]
            ],
            array_merge($this->defaultConfig(), [
                'connections' => [
                    'custom' => array_merge($this->defaultConnectionConfig(), [
                        'timeout' => [
                            'read' => 40,
                            'write' => 40,
                            'connect' => 40
                        ]
                    ])
                ]
            ])
        );
    }

    public function testVHostsDefaults()
    {
        $this->assertProcessedConfigurationEquals(
            [
                'boekkooi_amqp' => [
                    'vhosts' => [
                        'root' => [
                            'path' => '/'
                        ]
                    ]
                ]
            ],
            array_merge($this->defaultConfig(), [
                'vhosts' => [
                    'root' => $this->defaultVHostConfig()
                ]
            ])
        );
    }

    public function testVHostsExchanges()
    {
        $this->assertProcessedConfigurationEquals(
            [
                'boekkooi_amqp' => [
                    'vhosts' => [
                        'root' => [
                            'path' => '/',
                            'exchanges' => [
                                'dealer' => [
                                    'type' => 'direct'
                                ]
                            ]
                        ],
                        'extra' => [
                            'path' => '/extra',
                            'exchanges' => [
                                'dealer' => [
                                    'type' => 'fanout',
                                    'passive' => true,
                                    'durable' => false,
                                    'arguments' => [
                                        'headers' => [
                                            'test' => 123
                                        ],
                                        'content_type' => 'application/json'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            array_merge($this->defaultConfig(), [
                'vhosts' => [
                    'root' => array_merge($this->defaultVHostConfig(), [
                        'exchanges' => [
                            'dealer' => [
                                'type' => 'direct',
                                'passive' => false,
                                'durable' => true,
                                'arguments' => []
                            ]
                        ]
                    ]),
                    'extra' => array_merge($this->defaultVHostConfig('/extra'), [
                        'exchanges' => [
                            'dealer' => [
                                'type' => 'fanout',
                                'passive' => true,
                                'durable' => false,
                                'arguments' => [
                                    'headers' => [
                                        'test' => 123
                                    ],
                                    'content_type' => 'application/json'
                                ]
                            ]
                        ]
                    ])
                ]
            ])
        );
    }

    public function testVHostsExchangeRequireType()
    {
        $this->assertConfigurationIsInvalid(
            [
                'boekkooi_amqp' => [
                    'vhosts' => [
                        'root' => [
                            'path' => '/',
                            'exchanges' => [
                                'dealer' => []
                            ]
                        ]
                    ]
                ]
            ],
            'The child node "type" at path "boekkooi_amqp.vhosts.root.exchanges.dealer" must be configured.'
        );
    }

    public function testVHostsExchangeTypeEnum()
    {
        $this->assertConfigurationIsInvalid(
            [
                'boekkooi_amqp' => [
                    'vhosts' => [
                        'root' => [
                            'path' => '/',
                            'exchanges' => [
                                'dealer' => [
                                    'type' => 'evil'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'The value "evil" is not allowed for path "boekkooi_amqp.vhosts.root.exchanges.dealer.type". Permissible values:'
        );
    }

    public function testVHostQueue()
    {
        $this->assertProcessedConfigurationEquals(
            [
                'boekkooi_amqp' => [
                    'vhosts' => [
                        'root' => [
                            'path' => '/',
                            'queues' => [
                                'car' => [
                                    'binds' => [
                                        'my_exchange' => []
                                    ]
                                ],
                                'all' => [
                                    'passive' => true,
                                    'durable' => false,
                                    'exclusive' => true,
                                    'auto_delete' => true,
                                    'arguments' => [
                                        'some' => 'arg'
                                    ],
                                    'binds' => [
                                        'my_exchange' => [],
                                        'dope_exchange' => [
                                            'routing_key' => 'd',
                                            'arguments' => [
                                                'c' => 'arg'
                                            ]
                                        ]
                                    ]
                                ],
                            ]
                        ]
                    ]
                ]
            ],
            array_merge($this->defaultConfig(), [
                'vhosts' => [
                    'root' => array_merge($this->defaultVHostConfig(), [
                        'queues' => [
                            'car' => [
                                'passive' => false,
                                'durable' => true,
                                'exclusive' => false,
                                'auto_delete' => false,
                                'arguments' => [],
                                'binds' => [
                                    'my_exchange' => [
                                        'routing_key' => null,
                                        'arguments' => []
                                    ]
                                ]
                            ],
                            'all' => [
                                'passive' => true,
                                'durable' => false,
                                'exclusive' => true,
                                'auto_delete' => true,
                                'arguments' => [
                                    'some' => 'arg'
                                ],
                                'binds' => [
                                    'my_exchange' => [
                                        'routing_key' => null,
                                        'arguments' => []
                                    ],
                                    'dope_exchange' => [
                                        'routing_key' => 'd',
                                        'arguments' => [
                                            'c' => 'arg'
                                        ]
                                    ]
                                ]
                            ],
                        ]
                    ])
                ]
            ])
        );
    }

    public function testVHostQueueRequireBinds()
    {
        $this->assertConfigurationIsInvalid(
            [
                'boekkooi_amqp' => [
                    'vhosts' => [
                        'root' => [
                            'path' => '/',
                            'queues' => [
                                'car' => [],
                            ]
                        ]
                    ]
                ]
            ],
            'The child node "binds" at path "boekkooi_amqp.vhosts.root.queues.car" must be configured.'
        );
    }

    public function testVHostQueueRequireAtLeastOneBind()
    {
        $this->assertConfigurationIsInvalid(
            [
                'boekkooi_amqp' => [
                    'vhosts' => [
                        'root' => [
                            'path' => '/',
                            'queues' => [
                                'car' => [
                                    'binds' => []
                                ],
                            ]
                        ]
                    ]
                ]
            ],
            'The path "boekkooi_amqp.vhosts.root.queues.car.binds" should have at least 1 element(s) defined.'
        );
    }

    public function testCommands()
    {
        $this->assertProcessedConfigurationEquals(
            [
                'boekkooi_amqp' => [
                    'commands' => [
                        'my\\Command' => [
                            'vhost' => '/',
                            'exchange' => 'e'
                        ],
                        'my\\SecondCommand' => [
                            'vhost' => '/test',
                            'exchange' => 'a',
                            'routing_key' => 'key',
                            'rpc' => true,
                            'mandatory' => false,
                            'immediate' => true,
                            'attributes' => [
                                'some' => 'attr'
                            ]
                        ]
                    ]
                ]
            ],
            array_merge($this->defaultConfig(), [
                'commands' => [
                    'my\\Command' => [
                        'vhost' => '/',
                        'exchange' => 'e',
                        'routing_key' => null,
                        'rpc' => false,
                        'mandatory' => true,
                        'immediate' => false,
                        'attributes' => []
                    ],
                    'my\\SecondCommand' => [
                        'vhost' => '/test',
                        'exchange' => 'a',
                        'routing_key' => 'key',
                        'rpc' => true,
                        'mandatory' => false,
                        'immediate' => true,
                        'attributes' => [
                            'some' => 'attr'
                        ]
                    ]
                ]
            ])
        );
    }

    public function testCommandsRequireVhost()
    {
        $this->assertConfigurationIsInvalid(
            [
                'boekkooi_amqp' => [
                    'commands' => [
                        'my\\Class' => []
                    ]
                ]
            ],
            'The child node "vhost" at path "boekkooi_amqp.commands.my\\Class" must be configured.'
        );
    }

    public function testCommandsRequireExchange()
    {
        $this->assertConfigurationIsInvalid(
            [
                'boekkooi_amqp' => [
                    'commands' => [
                        'my\\Class' => [
                            'vhost' => '/'
                        ]
                    ]
                ]
            ],
            'The child node "exchange" at path "boekkooi_amqp.commands.my\\Class" must be configured.'
        );
    }

    public function testPublisherTransactions()
    {
        $this->assertProcessedConfigurationEquals(
            [
                'boekkooi_amqp' => [
                    'publisher' => [
                        'transaction' => [
                            'test',
                            'Yep'
                        ]
                    ]
                ]
            ],
            array_merge($this->defaultConfig(), [
                'publisher' => [
                    'transaction' => [
                        'test',
                        'Yep'
                    ]
                ]
            ])
        );
    }

    public function testPublisherTransactionsScalar()
    {
        $this->assertConfigurationIsInvalid(
            [
                'boekkooi_amqp' => [
                    'publisher' => [
                        'transaction' => [
                            [],
                        ]
                    ]
                ]
            ],
            'Invalid type for path "boekkooi_amqp.publisher.transaction.0". Expected scalar, but got array.'
        );
    }

    protected function defaultConfig()
    {
        return [
            'connections' => [],
            'vhosts' => [],
            'commands' => [],
            'command_bus' => 'tactician.commandbus',
            'envelope_transformer' => 'boekkooi.amqp.tactician.envelope_transformer',
            'command_transformer' => 'boekkooi.amqp.tactician.command_transformer',
            'response_transformer' => 'boekkooi.amqp.tactician.response_transformer',
            'serializer' => 'boekkooi.amqp.tactician.serializer',
            'serializer_format' => 'json',
            'publisher' => [ 'transaction' => [] ]
        ];
    }

    protected function defaultConnectionConfig()
    {
        return [
            'host' => 'localhost',
            'port' => 5672,
            'timeout' => [
                'read' => 10,
                'write' => 10,
                'connect' => 10
            ],
            'login' => 'guest',
            'password' => 'guest'
        ];
    }

    protected function defaultVHostConfig($path = '/')
    {
        return [
            'path' => $path,
            'connection' => 'default',
            'exchanges' => [],
            'queues' => []
        ];
    }
}
