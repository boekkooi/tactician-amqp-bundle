<?php
namespace Boekkooi\Bundle\AMQP\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * This is the class that validates and merges configuration from your app/config files
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('boekkooi_amqp');

        $this->addConnections($rootNode);
        $this->addVHosts($rootNode);
        $this->addCommands($rootNode);
        $this->addPublisher($rootNode);

        $this->addTactician($rootNode);

        return $treeBuilder;
    }

    protected function addConnections(ArrayNodeDefinition $node)
    {
        $integerNormalizer = function ($val) { return strval(intval($val)) === $val ? intval($val) : $val; };

        $node
            ->children()
                ->arrayNode('connections')
                    ->useAttributeAsKey('name')
                    ->canBeUnset()
                    ->prototype('array')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('host')->defaultValue('localhost')->end()
                            ->integerNode('port')
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then($integerNormalizer)
                                ->end()
                                ->defaultValue(5672)
                            ->end()
                            ->arrayNode('timeout')
                                ->addDefaultsIfNotSet()
                                ->beforeNormalization()
                                    ->ifTrue(function ($v) { return is_int($v); })
                                    ->then(function ($v) { return array('read' => $v, 'write' => $v, 'connect' => $v); })
                                ->end()
                                ->children()
                                    ->integerNode('read')
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then($integerNormalizer)
                                        ->end()
                                        ->defaultValue(10)
                                    ->end()
                                    ->integerNode('write')
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then($integerNormalizer)
                                        ->end()
                                        ->defaultValue(10)
                                    ->end()
                                    ->integerNode('connect')
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then($integerNormalizer)
                                        ->end()
                                        ->defaultValue(10)
                                    ->end()
                                ->end()
                            ->end()
                            ->scalarNode('login')->defaultValue('guest')->end()
                            ->scalarNode('password')->defaultValue('guest')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    protected function addVHosts(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('vhosts')
                    ->useAttributeAsKey('name')
                    ->canBeUnset()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('path')->info('The actual vhost')->isRequired()->end()
                            ->scalarNode('connection')->defaultValue('default')->end()

                            ->arrayNode('exchanges')
                                ->useAttributeAsKey('name')
                                ->canBeUnset()
                                ->prototype('array')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->enumNode('type')
                                            ->values(array('direct', 'fanout', 'headers', 'topic'))
                                            ->isRequired()
                                        ->end()
                                        ->booleanNode('passive')->defaultFalse()->end()
                                        ->booleanNode('durable')->defaultTrue()->end()
                                        ->arrayNode('arguments')
                                            ->defaultValue(array())
                                            ->prototype('variable')->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()

                            ->arrayNode('queues')
                                ->useAttributeAsKey('name')
                                ->canBeUnset()
                                ->prototype('array')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->booleanNode('passive')->defaultFalse()->end()
                                        ->booleanNode('durable')->defaultTrue()->end()
                                        ->booleanNode('exclusive')->defaultFalse()->end()
                                        ->booleanNode('auto_delete')->defaultFalse()->end()

                                        ->arrayNode('arguments')
                                            ->defaultValue(array())
                                            ->prototype('variable')->end()
                                        ->end()

                                        ->arrayNode('binds')
                                            ->isRequired()
                                            ->requiresAtLeastOneElement()
                                            ->useAttributeAsKey('exchange')
                                            ->prototype('array')
                                                ->addDefaultsIfNotSet()
                                                ->children()
                                                    ->scalarNode('routing_key')->defaultValue(null)->end()
                                                    ->arrayNode('arguments')
                                                        ->defaultValue(array())
                                                        ->prototype('variable')->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()

                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    protected function addCommands(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('commands')
                    ->useAttributeAsKey('class')
                    ->canBeUnset()
                    ->prototype('array')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('vhost')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('exchange')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('routing_key')->defaultValue(null)->end()

                            ->booleanNode('rpc')
                                ->info('the command is expected to return a response')
                                ->defaultFalse()
                            ->end()
                            ->booleanNode('mandatory')
                                ->info('the command must be routed to a valid queue')
                                ->defaultTrue()
                            ->end()
                            ->booleanNode('immediate')
                                ->info('mark the command for immediate processing')
                                ->defaultFalse()
                            ->end()

                            ->arrayNode('attributes')
                                ->defaultValue(array())
                                ->prototype('variable')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addTactician(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->scalarNode('command_bus')
                    ->info('The tactician command bus to use for queue consumption')
                    ->defaultValue('tactician.commandbus')
                ->end()
                ->scalarNode('envelope_transformer')->defaultValue('boekkooi.amqp.tactician.envelope_transformer')->end()
                ->scalarNode('command_transformer')->defaultValue('boekkooi.amqp.tactician.command_transformer')->end()
                ->scalarNode('response_transformer')->defaultValue('boekkooi.amqp.tactician.response_transformer')->end()
                ->scalarNode('serializer')->defaultValue('boekkooi.amqp.tactician.serializer')->end()
                ->scalarNode('serializer_format')->defaultValue('json')->end()
            ->end();
    }

    private function addPublisher(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('publisher')
                    ->addDefaultsIfNotSet()
                    ->canBeUnset()
                    ->children()
                        ->arrayNode('transaction')
                            ->defaultValue(array())
                            ->prototype('scalar')
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
