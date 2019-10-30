<?php

declare(strict_types=1);

namespace Facile\MongoDbBundle\DataCollector;

use Facile\MongoDbBundle\Models\Query;
use Facile\MongoDbBundle\Services\Loggers\DataCollectorLoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Class MongoDbDataCollector.
 *
 * @internal
 */
class MongoDbDataCollector extends DataCollector
{
    const QUERY_KEYWORD = 'queries';

    const CONNECTION_KEYWORD = 'connections';

    const TIME_KEYWORD = 'totalTime';

    /** @var DataCollectorLoggerInterface */
    private $logger;

    /** @var array */
    protected $data;

    public function __construct()
    {
        $this->reset();
    }

    public function reset()
    {
        $this->data = [
            self::QUERY_KEYWORD => [],
            self::TIME_KEYWORD => 0.0,
            self::CONNECTION_KEYWORD => [],
        ];
    }

    public function setLogger(DataCollectorLoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        while ($this->logger->hasLoggedEvents()) {
            /** @var Query $event */
            $event = $this->logger->getLoggedEvent();

            MongoQuerySerializer::serialize($event);

            $this->data[self::QUERY_KEYWORD][] = $event;
            $this->data[self::TIME_KEYWORD] += $event->getExecutionTime();
        }

        $this->data[self::CONNECTION_KEYWORD] = $this->logger->getConnections();
    }

    public function getQueryCount(): int
    {
        return \count($this->data[self::QUERY_KEYWORD]);
    }

    /**
     * @return Query[]|array
     */
    public function getQueries(): array
    {
        return $this->data[self::QUERY_KEYWORD];
    }

    public function getTime(): float
    {
        return (float) ($this->data[self::TIME_KEYWORD] * 1000);
    }

    public function getConnectionsCount(): int
    {
        return \count($this->data[self::CONNECTION_KEYWORD]);
    }

    /**
     * @return array|string[]
     */
    public function getConnections(): array
    {
        return $this->data[self::CONNECTION_KEYWORD];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'mongodb';
    }
}
