<?php

declare(strict_types=1);

namespace Forecor\Core;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\Event as ContractEvent;

/**
 * Merkezi event dispatch: Event::dispatch('topic.created', $event);
 * Eklentiler config/events.php ile listener ekleyerek çekirdeği değiştirmez.
 */
final class Event
{
    private static ?EventDispatcherInterface $dispatcher = null;

    public static function setDispatcher(EventDispatcherInterface $dispatcher): void
    {
        self::$dispatcher = $dispatcher;
    }

    public static function getDispatcher(): ?EventDispatcherInterface
    {
        return self::$dispatcher;
    }

    /**
     * Event fırlat. $payload Event nesnesiyse doğrudan, değilse GenericEvent ile sarılır.
     *
     * Örnek:
     *   Event::dispatch(Events::TOPIC_CREATED, new \App\Events\TopicCreated($topic));
     *   Event::dispatch('thread.created', [$thread]);
     */
    public static function dispatch(string $eventName, object|array $payload = []): object
    {
        $dispatcher = self::$dispatcher;
        if ($dispatcher === null) {
            return $payload instanceof ContractEvent ? $payload : new GenericEvent($payload);
        }

        if ($payload instanceof ContractEvent) {
            return $dispatcher->dispatch($payload, $eventName);
        }

        $event = is_array($payload)
            ? new GenericEvent(null, $payload)
            : new GenericEvent($payload, ['subject' => $payload]);
        return $dispatcher->dispatch($event, $eventName);
    }
}
