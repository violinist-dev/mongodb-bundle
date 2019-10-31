<?php

namespace Facile\MongoDbBundle\DependencyInjection;

use Facile\MongoDbBundle\Utils\AntiDeprecationUtils;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration.
 *
 * @internal
 */
final class Configuration implements ConfigurationInterface
{
    const READ_PREFERENCE_VALID_OPTIONS = ['primary', 'primaryPreferred', 'secondary', 'secondaryPreferred', 'nearest'];

    const K_mongoDbBundle = 'mongo_db_bundle';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::K_mongoDbBundle);
        $rootBuilder = AntiDeprecationUtils::rootNode(
            $treeBuilder,
            self::K_mongoDbBundle
        );

        self::addDataCollection($rootBuilder->children());
        self::addClients($rootBuilder->children());
        self::addConnections($rootBuilder->children());

        return $treeBuilder;
    }

    private static function addDataCollection(NodeBuilder $builder): void
    {
        $builder
            ->booleanNode('data_collection')
            ->defaultTrue()
            ->info('Disables Data Collection if needed');
    }

    private static function addClients(NodeBuilder $builder): void
    {
        /** @var ArrayNodeDefinition $clientsBuilder */
        $clientsBuilder = $builder
            ->arrayNode('clients')
            ->isRequired()
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('name')
            ->prototype('array');

        $nodeBuilder = $clientsBuilder->children();

        self::addClientsHosts($nodeBuilder);

        $nodeBuilder
            ->scalarNode('uri')
            ->defaultNull()
            ->info('Overrides hosts configuration');

        $nodeBuilder
            ->scalarNode('username')
            ->defaultValue('');

        $nodeBuilder
            ->scalarNode('password')
            ->defaultValue('');

        $nodeBuilder
            ->scalarNode('authSource')
            ->defaultNull()
            ->info('Database name associated with the user’s credentials');

        $nodeBuilder
            ->scalarNode('readPreference')
            ->defaultValue('primaryPreferred')
            ->validate()
            ->ifNotInArray(self::READ_PREFERENCE_VALID_OPTIONS)
            ->thenInvalid('Invalid readPreference option %s, must be one of [' . implode(', ', self::READ_PREFERENCE_VALID_OPTIONS) . ']');

        $nodeBuilder
            ->scalarNode('replicaSet')
            ->defaultNull();

        $nodeBuilder
            ->booleanNode('ssl')
            ->defaultFalse();

        $nodeBuilder
            ->integerNode('connectTimeoutMS')
            ->defaultNull();
    }

    private static function addClientsHosts(NodeBuilder $builder): void
    {
        /** @var ArrayNodeDefinition $hostsBuilder */
        $hostsBuilder = $builder
            ->arrayNode('hosts')
            ->info('Hosts addresses and ports')
            ->prototype('array');

        $nodeBuilder = $hostsBuilder->children();

        $nodeBuilder
            ->scalarNode('host')
            ->isRequired();

        $nodeBuilder
            ->integerNode('port')
            ->defaultValue(27017);
    }

    private static function addConnections(NodeBuilder $builder): void
    {
        /** @var ArrayNodeDefinition $connectionBuilder */
        $connectionBuilder = $builder
            ->arrayNode('connections')
            ->isRequired()
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('name')
            ->prototype('array');

        $nodeBuilder = $connectionBuilder->children();

        $nodeBuilder
            ->scalarNode('client_name')
            ->isRequired()
            ->info('Desired client name');

        $nodeBuilder
            ->scalarNode('database_name')
            ->isRequired()
            ->info('Database name');
    }
}
