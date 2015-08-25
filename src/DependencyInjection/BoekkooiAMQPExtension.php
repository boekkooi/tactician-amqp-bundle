<?php
namespace Boekkooi\Bundle\AMQP\DependencyInjection;

use Boekkooi\Bundle\AMQP\CommandConfiguration;
use Boekkooi\Bundle\AMQP\Consumer\Consumer;
use Boekkooi\Bundle\AMQP\Exception\InvalidConfigurationException;
use Boekkooi\Bundle\AMQP\LazyChannel;
use Boekkooi\Bundle\AMQP\LazyConnection;
use Boekkooi\Bundle\AMQP\LazyQueue;
use Boekkooi\Bundle\AMQP\LazyExchange;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 */
class BoekkooiAMQPExtension extends Extension
{
    const SERVICE_VHOST_CONNECTION_ID = 'boekkooi.amqp.vhost.%s.connection';
    const SERVICE_VHOST_CHANNEL_ID = 'boekkooi.amqp.vhost.%s.channel';
    const SERVICE_VHOST_CONSUMER_ID = 'boekkooi.amqp.vhost.%s.consumer';
    const SERVICE_VHOST_EXCHANGE_ID = 'boekkooi.amqp.vhost.%s.exchange.%s';
    const SERVICE_VHOST_QUEUE_ID = 'boekkooi.amqp.vhost.%s.queue.%s';

    const PARAMETER_VHOST_LIST = 'boekkooi.amqp.vhosts';
    const PARAMETER_VHOST_EXCHANGE_LIST = 'boekkooi.amqp.vhost.%s.exchanges';
    const PARAMETER_VHOST_QUEUE_LIST = 'boekkooi.amqp.vhost.%s.queues';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config/services'));

        $loader->load('amqp.yml');
        $this->loadVhosts($container, $config);

        $loader->load('tactician.yml');
        $this->configureCommands($container, $config);
        $this->configureTransformers($container, $config);
        $this->configureMiddleware($container, $config);

        $this->configureConsoleCommands($container, $config);
    }

    private function loadVhosts(ContainerBuilder $container, array $config)
    {
        $vhosts = [];
        foreach ($config['vhosts'] as $name => $info) {
            if (!isset($config['connections'][$info['connection']])) {
                throw InvalidConfigurationException::noConnectionForVHost($info['connection'], $name);
            }

            // Create connection
            $connectionId = sprintf(self::SERVICE_VHOST_CONNECTION_ID, $name);
            $connInfo = $config['connections'][$info['connection']];
            $container->setDefinition(
                $connectionId,
                new Definition(LazyConnection::class, [
                    $connInfo['host'],
                    $connInfo['port'],
                    $info['path'],
                    $connInfo['login'],
                    $connInfo['password'],
                    $connInfo['timeout']['read'],
                    $connInfo['timeout']['write'],
                    $connInfo['timeout']['connect'],
                ])
            );

            // Create channel
            $channelId = sprintf(self::SERVICE_VHOST_CHANNEL_ID, $name);
            $container->setDefinition(
                $channelId,
                new Definition(LazyChannel::class)
            );

            // Create exchanges
            $this->loadVHostExchanges($container, $name, $info);

            // Create queues
            $this->loadVHostQueues($container, $name, $info);

            // Create queue locator
            $queueLocatorId = sprintf('boekkooi.amqp.vhost.%s.queue_locator', $name);
            $def = new DefinitionDecorator('boekkooi.amqp.abstract.consumer.queue_locator');
            $def->addArgument($name);
            $container->setDefinition($queueLocatorId, $def);

            // Create consumer
            $container->setDefinition(
                sprintf(self::SERVICE_VHOST_CONSUMER_ID, $name),
                new Definition(Consumer::class, [
                    new Reference($connectionId),
                    new Reference($channelId),
                    new Reference($queueLocatorId)
                ])
            );

            $vhosts[] = $name;
        }

        $container->setParameter(self::PARAMETER_VHOST_LIST, $vhosts);
    }

    private function loadVHostExchanges(ContainerBuilder $container, $vhost, array $config)
    {
        $typeMap = [
            'direct' => AMQP_EX_TYPE_DIRECT,
            'fanout' => AMQP_EX_TYPE_FANOUT,
            'headers' => AMQP_EX_TYPE_HEADERS,
            'topic' => AMQP_EX_TYPE_TOPIC
        ];

        $exchanges = [];
        foreach ($config['exchanges'] as $name => $info) {
            $container->setDefinition(
                sprintf(self::SERVICE_VHOST_EXCHANGE_ID, $vhost, $name),
                new Definition(LazyExchange::class, [
                    $name,
                    $typeMap[$info['type']],
                    $info['passive'],
                    $info['durable'],
                    $info['arguments']
                ])
            );

            $exchanges[] = $name;
        }

        $container->setParameter(sprintf(self::PARAMETER_VHOST_EXCHANGE_LIST, $vhost), $exchanges);
    }

    private function loadVHostQueues(ContainerBuilder $container, $vhost, array $config)
    {
        $queues = [];
        foreach ($config['queues'] as $name => $info) {
            $serviceId = sprintf(self::SERVICE_VHOST_QUEUE_ID, $vhost, $name);

            $container->setDefinition(
                $serviceId,
                new Definition(LazyQueue::class, [
                    $name,
                    $info['passive'],
                    $info['durable'],
                    $info['exclusive'],
                    $info['auto_delete'],
                    $info['arguments'],
                    $info['binds']
                ])
            );

            $queues[] = $name;
        }

        $container->setParameter(sprintf(self::PARAMETER_VHOST_QUEUE_LIST, $vhost), $queues);
    }

    private function configureCommands(ContainerBuilder $container, array $config)
    {
        $transformerDef = $container->getDefinition('boekkooi.amqp.tactician.command_transformer');
        $middlewareDef = $container->getDefinition('boekkooi.amqp.middleware.command_transformer');

        $basicCommands = [];
        $rpcCommands = [];
        foreach ($config['commands'] as $class => $info) {
            $commandReference = $this->configureCommand($container, $class, $info);

            $transformerDef->addMethodCall('registerCommand', [ $commandReference ]);
            $middlewareDef->addMethodCall('addSupportedCommand', [ $class ]);

            if ($info['rpc'] === true) {
                $rpcCommands[] = $class;
            } else {
                $basicCommands[] = $class;
            }
        }

        $publisherLocator = $container->getDefinition('boekkooi.amqp.tactician.publisher_locator');
        $publisherLocator->addMethodCall(
            'registerPublisher',
            [
                new Reference('boekkooi.amqp.tactician.publisher.basic'),
                $basicCommands
            ]
        );
        $publisherLocator->addMethodCall(
            'registerPublisher',
            [
                new Reference('boekkooi.amqp.tactician.publisher.rpc'),
                $rpcCommands
            ]
        );
    }

    private function configureCommand(ContainerBuilder $container, $class, array $info)
    {
        $commandConfig = new CommandConfiguration(
            $class,
            $info['vhost'],
            $info['exchange'],
            (isset($info['routing_key']) ? $info['routing_key'] : null),
            (
                (isset($info['mandatory']) && $info['mandatory'] ? AMQP_MANDATORY : AMQP_NOPARAM) |
                (isset($info['immediate']) && $info['immediate'] ? AMQP_IMMEDIATE : AMQP_NOPARAM)
            ),
            (isset($info['attributes']) ? $info['attributes'] : [])
        );

        if (!$container->hasDefinition(sprintf(self::SERVICE_VHOST_CONNECTION_ID, $commandConfig->getVhost()))) {
            throw InvalidConfigurationException::unknownVHostForCommand(
                $commandConfig->getClass(),
                $commandConfig->getVhost()
            );
        }
        if (!$container->hasDefinition(sprintf(self::SERVICE_VHOST_EXCHANGE_ID, $commandConfig->getVhost(), $commandConfig->getExchange()))) {
            throw InvalidConfigurationException::unknownExchangeForCommand(
                $commandConfig->getClass(),
                $commandConfig->getVhost(),
                $commandConfig->getExchange()
            );
        }

        $commandId = sprintf(
            'boekkooi.amqp.vhost.%s.command.config.%s',
            $commandConfig->getVhost(),
            sha1(implode('|', [
                $commandConfig->getClass(),
                $commandConfig->getVhost(),
                $commandConfig->getExchange(),
                $commandConfig->getRoutingKey(),
                $commandConfig->getFlags(),
                count($commandConfig->getAttributes())
            ]))
        );
        $commandDef = new Definition(
            CommandConfiguration::class,
            [
                $commandConfig->getClass(),
                $commandConfig->getVhost(),
                $commandConfig->getExchange(),
                $commandConfig->getRoutingKey(),
                $commandConfig->getFlags(),
                $commandConfig->getAttributes()
            ]
        );
        $commandDef->setPublic(false);
        $container->setDefinition($commandId, $commandDef);

        return new Reference($commandId);
    }

    private function configureTransformers(ContainerBuilder $container, array $config)
    {
        $container
            ->setParameter(
                'boekkooi.amqp.tactician.serializer.format',
                $config['serializer_format']
            );
        $container
            ->setParameter(
                'boekkooi.amqp.tactician.serializer.service',
                $config['serializer']
            );

        $commandSerializer = new Reference($config['serializer']);
        foreach (['command_transformer', 'envelope_transformer', 'response_transformer'] as $transformer) {
            $container
                ->getDefinition('boekkooi.amqp.tactician.' . $transformer)
                ->replaceArgument(0, $commandSerializer);
        }
    }

    private function configureMiddleware(ContainerBuilder $container, array $config)
    {
        $envelopeTransformer = new Reference($config['envelope_transformer']);
        $container
            ->getDefinition('boekkooi.amqp.middleware.envelope_transformer')
            ->replaceArgument(0, $envelopeTransformer);

        $commandTransformer = new Reference($config['command_transformer']);
        $container
            ->getDefinition('boekkooi.amqp.middleware.command_transformer')
            ->replaceArgument(0, $commandTransformer);

        $responseTransformer = new Reference($config['response_transformer']);
        $container
            ->getDefinition('boekkooi.amqp.middleware.remote_response')
            ->replaceArgument(0, $responseTransformer);
    }

    private function configureConsoleCommands(ContainerBuilder $container, array $config)
    {
        $container
            ->setAlias('boekkooi.amqp.consume_command_bus', $config['command_bus']);
    }
}
