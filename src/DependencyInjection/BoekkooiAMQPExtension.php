<?php
namespace Boekkooi\Bundle\AMQP\DependencyInjection;

use Boekkooi\Bundle\AMQP\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 */
class BoekkooiAMQPExtension extends Extension
{
    const SERVICE_CONNECTION_ID = 'boekkooi.amqp.connection.%s';
    const SERVICE_VHOST_CONNECTION_ID = 'boekkooi.amqp.vhost.%s.connection';
    const SERVICE_VHOST_CHANNEL_ID = 'boekkooi.amqp.vhost.%s.channel';
    const SERVICE_VHOST_EXCHANGE_ID = 'boekkooi.amqp.vhost.%s.exchange.%s';
    const SERVICE_VHOST_QUEUE_ID = 'boekkooi.amqp.vhost.%s.queue.%s';
    const PARAMETER_VHOST_MAP = 'boekkooi.amqp.vhost.map';
    const PARAMETER_VHOST_QUEUE_BINDS = 'boekkooi.amqp.vhost.%s.queue.%s.binds';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config/services'));

        $loader->load('amqp.yml');
        $this->loadConnections($container, $config);
        $this->loadVHost($container, $config);

        $loader->load('tactician.yml');
        $this->configureCommands($container, $config);
        $this->configureMiddleware($container, $config);

        $loader->load('symfony.yml');
    }

    private function loadConnections(ContainerBuilder $container, array $config)
    {
        foreach ($config['connections'] as $name => $info) {
            $args = [
                'host' => $info['host'],
                'port' => $info['port'],
                'login' => $info['login'],
                'password' => $info['password'],
                'read_timeout' => $info['timeout']['read'],
                'write_timeout' => $info['timeout']['write'],
                'connect_timeout' => $info['timeout']['connect'],
            ];

            $def = new DefinitionDecorator('boekkooi.amqp.abstract.vhost_connection');
            $def->setPublic(false);
            $def->setAbstract(true);
            $def->replaceArgument(0, $args);
            $container->setDefinition(sprintf(self::SERVICE_CONNECTION_ID, $name), $def);
        }
    }

    private function loadVHost(ContainerBuilder $container, array $config)
    {
        foreach ($config['vhosts'] as $name => $info) {
            $connectionServiceId = sprintf(self::SERVICE_CONNECTION_ID, $info['connection']);
            if (!$container->hasDefinition($connectionServiceId)) {
                throw InvalidConfigurationException::noConnectionForVHost($info['connection'], $name);
            }

            $vhostConnectionServiceId = sprintf(self::SERVICE_VHOST_CONNECTION_ID, $name);
            $vhostChannelServiceId = sprintf(self::SERVICE_VHOST_CHANNEL_ID, $name);

            // Create connection service
            $def = new DefinitionDecorator($connectionServiceId);
            $def->addMethodCall('setVhost', [ $info['path'] ]);
            $container->setDefinition($vhostConnectionServiceId, $def);

            // Create channel service
            $def = new DefinitionDecorator('boekkooi.amqp.abstract.channel');
            $def->replaceArgument(0, new Reference($vhostConnectionServiceId));
            $container->setDefinition($vhostChannelServiceId, $def);
            $channelServiceRef = new Reference($vhostChannelServiceId);

            // Create exchanges
            $this->loadVHostExchanges($container, $name, $info, $channelServiceRef);

            // Create queues
            $this->loadVHostQueues($container, $name, $info, $channelServiceRef);
        }
    }

    private function loadVHostExchanges(ContainerBuilder $container, $vhost, array $config, Reference $channel)
    {
        $typeMap = [
            'direct' => AMQP_EX_TYPE_DIRECT,
            'fanout' => AMQP_EX_TYPE_FANOUT,
            'headers' => AMQP_EX_TYPE_HEADERS,
            'topic' => AMQP_EX_TYPE_TOPIC
        ];

        foreach ($config['exchanges'] as $name => $info) {
            $def = new DefinitionDecorator('boekkooi.amqp.abstract.exchange');
            $def->setPublic(true);
            $def->replaceArgument(0, $channel);

            $def->addMethodCall('setName', [ $name ]);
            $def->addMethodCall('setArguments', [ $info['arguments'] ]);
            $def->addMethodCall('setType', [
                $typeMap[$info['type']]
            ]);
            $def->addMethodCall('setFlags', [
                ($info['passive'] ? AMQP_PASSIVE : AMQP_NOPARAM) |
                ($info['durable'] ? AMQP_DURABLE : AMQP_NOPARAM)
            ]);

            $container->setDefinition(
                sprintf(self::SERVICE_VHOST_EXCHANGE_ID, $vhost, $name),
                $def
            );
        }
    }

    private function loadVHostQueues(ContainerBuilder $container, $vhost, array $config, Reference $channel)
    {
        foreach ($config['queues'] as $name => $info) {
            $def = new DefinitionDecorator('boekkooi.amqp.abstract.queue');
            $def->setPublic(true);
            $def->replaceArgument(0, $channel);

            $def->addMethodCall('setName', [ $name ]);
            $def->addMethodCall('setArguments', [ $info['arguments'] ]);
            $def->addMethodCall('setFlags', [
                ($info['passive'] ? AMQP_PASSIVE : AMQP_NOPARAM) |
                ($info['durable'] ? AMQP_DURABLE : AMQP_NOPARAM) |
                ($info['exclusive'] ? AMQP_EXCLUSIVE : AMQP_NOPARAM) |
                ($info['auto_delete'] ? AMQP_AUTODELETE : AMQP_NOPARAM)
            ]);

            $container->setDefinition(
                sprintf(self::SERVICE_VHOST_QUEUE_ID, $vhost, $name),
                $def
            );

            $container->setParameter(
                sprintf(self::PARAMETER_VHOST_QUEUE_BINDS, $vhost, $name),
                $info['binds']
            );
        }
    }

    private function configureCommands(ContainerBuilder $container, array $config)
    {
        $commands = [];
        foreach ($config['commands'] as $class => $info) {
            if (!$container->hasDefinition(sprintf(self::SERVICE_VHOST_CONNECTION_ID, $info['vhost']))) {
                throw InvalidConfigurationException::unknownVHostForCommand($class, $info['vhost']);
            }
            if (!$container->hasDefinition(sprintf(self::SERVICE_VHOST_EXCHANGE_ID, $info['vhost'], $info['exchange']))) {
                throw InvalidConfigurationException::unknownExchangeForCommand($class, $info['vhost'], $info['exchange']);
            }

            $class = ltrim($class, '\\');
            $commands[$class] = [
                'vhost' => $info['vhost'],
                'exchange' => $info['exchange'],
                'routing_key' => (isset($info['routing_key']) ? $info['routing_key'] : null),
                'flags' => (
                    (isset($info['mandatory']) && $info['mandatory'] ? AMQP_MANDATORY : AMQP_NOPARAM) |
                    (isset($info['immediate']) && $info['immediate'] ? AMQP_IMMEDIATE : AMQP_NOPARAM)
                ),
                'attributes' => (isset($info['attributes']) ? $info['attributes'] : [])
            ];
        }

        $def = $container->getDefinition('boekkooi.amqp.tactician.transformer');
        $def->addMethodCall('addCommands', [$commands]);

        $def = $container->getDefinition('boekkooi.amqp.middleware.command_transformer');
        $def->addMethodCall('addSupportedCommands', [array_keys($commands)]);
    }

    private function configureMiddleware(ContainerBuilder $container, array $config)
    {
        $commandTransformer = new Reference($config['envelope_transformer']);
        $container
            ->getDefinition('boekkooi.amqp.middleware.envelope_transformer')
            ->replaceArgument(0, $commandTransformer);

        $commandTransformer = new Reference($config['command_transformer']);
        $container
            ->getDefinition('boekkooi.amqp.middleware.command_transformer')
            ->replaceArgument(0, $commandTransformer);

        $commandSerializer = new Reference($config['serializer']);
        $container
            ->getDefinition('boekkooi.amqp.tactician.transformer')
            ->replaceArgument(0, $commandSerializer);
        $container
            ->setParameter(
                'boekkooi.amqp.tactician.serializer.format',
                $config['serializer_format']
            );
    }
}
