<?php

declare(strict_types=1);

namespace Facile\MongoDbBundle\Services;

use Facile\MongoDbBundle\Capsule\Client as BundleClient;
use Facile\MongoDbBundle\Event\ConnectionEvent;
use Facile\MongoDbBundle\Models\ClientConfiguration;
use MongoDB\Client;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\LegacyEventDispatcherProxy;

/**
 * Class ClientRegistry.
 *
 * @internal
 */
final class ClientRegistry
{
    /** @var Client[] */
    private $clients;

    /** @var ClientConfiguration[] */
    private $configurations;

    /** @var bool */
    private $debug;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher, bool $debug)
    {
        $this->clients = [];
        $this->configurations = [];
        $this->debug = $debug;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function addClientsConfigurations(array $configurations): void
    {
        foreach ($configurations as $name => $conf) {
            $this->addClientConfiguration($name, $conf);
        }
    }

    private function addClientConfiguration(string $name, array $conf): void
    {
        $this->configurations[$name] = $this->buildClientConfiguration($conf);
    }

    private function buildClientConfiguration(array $conf): ClientConfiguration
    {
        if (! $conf['uri']) {
            $conf['uri'] = self::buildConnectionUri($conf['hosts']);
        }

        return new ClientConfiguration(
            $conf['uri'],
            $conf['username'],
            $conf['password'],
            $conf['authSource'],
            [
                'replicaSet' => $conf['replicaSet'],
                'ssl' => $conf['ssl'],
                'connectTimeoutMS' => $conf['connectTimeoutMS'],
                'readPreference' => $conf['readPreference'],
            ]
        );
    }

    private static function buildConnectionUri(array $hosts): string
    {
        return 'mongodb://' . implode(
            ',',
            array_map(
                static function (array $host) {
                    return sprintf('%s:%d', $host['host'], $host['port']);
                },
                $hosts
            )
        );
    }

    public function getClientForDatabase(string $name, string $databaseName): Client
    {
        return $this->getClient($name, $databaseName);
    }

    public function getClientNames(): array
    {
        return array_keys($this->clients);
    }

    public function getClient(string $name, string $databaseName = null): Client
    {
        $clientKey = null !== $databaseName ? $name . '.' . $databaseName : $name;

        if (! isset($this->clients[$clientKey])) {
            $conf = $this->configurations[$name];
            $options = array_merge(
                [
                    'database' => $databaseName,
                    'authSource' => $conf->getAuthSource() ?? $databaseName ?? 'admin',
                ],
                $conf->getOptions()
            );
            $this->clients[$clientKey] = $this->buildClient($name, $conf->getUri(), $options, []);

            $event = new ConnectionEvent($clientKey);
            $this->eventDispatcher->dispatch(ConnectionEvent::CLIENT_CREATED, $event);
        }

        return $this->clients[$clientKey];
    }

    /**
     * @param string $clientName
     * @param string $uri
     * @param array  $options
     * @param array  $driverOptions
     *
     * @return Client
     */
    private function buildClient(string $clientName, string $uri, array $options, array $driverOptions): Client
    {
        if (true === $this->debug) {
            return new BundleClient($uri, $options, $driverOptions, $clientName, $this->eventDispatcher);
        }

        return new Client($uri, $options, $driverOptions);
    }
}
