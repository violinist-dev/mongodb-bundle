<?php

declare(strict_types=1);

namespace Facile\MongoDbBundle\DataCollector;

use Facile\MongoDbBundle\Models\Query;
use MongoDB\BSON\Serializable;

/**
 * Class MongoQuerySerializer
 *
 * @internal
 */
final class MongoQuerySerializer
{
    public static function serialize(Query $query)
    {
        $query->setFilters(self::prepareUnserializableData($query->getFilters()));
        $query->setData(self::prepareUnserializableData($query->getData()));
        $query->setOptions(self::prepareUnserializableData($query->getOptions()));
    }

    private static function prepareUnserializableData($data): array
    {
        if ($data instanceof Serializable) {
            $data = $data->bsonSerialize();
        }

        $newData = [];
        foreach ($data as $key => $item) {
            $newData[$key] = self::prepareItemData($item);
        }

        return $newData;
    }

    public static function prepareItemData($item)
    {
        if (\is_string($item)) {
            return $item;
        }

        if (method_exists($item, 'getArrayCopy')) {
            return self::prepareUnserializableData($item->getArrayCopy());
        }

        if (method_exists($item, 'toDateTime')) {
            return 'ISODate("' . $item->toDateTime()->format('c') . '")';
        }

        if (method_exists($item, '__toString')) {
            return $item->__toString();
        }

        if ($item instanceof Serializable) {
            return $item->bsonSerialize();
        }

        if (\is_array($item) || \is_object($item)) {
            return self::prepareUnserializableData((array) $item);
        }

        return $item;
    }
}
