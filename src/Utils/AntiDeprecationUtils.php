<?php

declare(strict_types=1);

namespace Facile\MongoDbBundle\Utils;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\LegacyEventDispatcherProxy;

final class AntiDeprecationUtils
{
    private function __construct()
    {
    }

    public static function safeDispatch(
        EventDispatcherInterface $eventDispatcher,
        string $name,
        Event $event
    ): ?Event {
        if (class_exists(LegacyEventDispatcherProxy::class)) {
            return $eventDispatcher->dispatch($event, $name);
        }

        return $eventDispatcher->dispatch($name, $event);
    }
}
