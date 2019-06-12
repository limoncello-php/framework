<?php declare(strict_types=1);

namespace Limoncello\Tests\Events\Data\Subscribers;

/**
 * Copyright 2015-2019 info@neomerx.com
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use Limoncello\Events\Contracts\EventHandlerInterface;
use Limoncello\Tests\Events\Data\Events\BaseOrderEvent;
use Limoncello\Tests\Events\Data\Events\OrderCreatedEvent;
use Limoncello\Tests\Events\Data\Events\OrderEvent;
use Limoncello\Tests\Events\Data\Events\OrderUpdatedEvent;
use function assert;

/**
 * @package Limoncello\Tests\Events
 */
class OrderSubscribers implements EventHandlerInterface
{
    /**
     * @var bool
     */
    private static $onOrder = false;

    /**
     * @var bool
     */
    private static $onBaseOrder = false;

    /**
     * @var bool
     */
    private static $onOrderCreated = false;

    /**
     * @var bool
     */
    private static $onOrderUpdated = false;

    /**
     * @return void
     */
    public static function reset()
    {
        static::$onOrder        = false;
        static::$onBaseOrder    = false;
        static::$onOrderCreated = false;
        static::$onOrderUpdated = false;
    }

    /**
     * @param OrderEvent $event
     */
    public static function onOrder(OrderEvent $event)
    {
        assert($event);

        static::$onOrder = true;
    }

    /**
     * @return bool
     */
    public static function isOnOrder()
    {
        return self::$onOrder;
    }

    /**
     * @param BaseOrderEvent $event
     */
    public static function onBaseOrder(BaseOrderEvent $event)
    {
        assert($event);

        static::$onBaseOrder = true;
    }

    /**
     * @return bool
     */
    public static function isOnBaseOrder(): bool
    {
        return self::$onBaseOrder;
    }

    /**
     * @param OrderCreatedEvent $event
     */
    public static function onOrderCreated(OrderCreatedEvent $event)
    {
        assert($event);

        static::$onOrderCreated = true;
    }

    /**
     * @return bool
     */
    public static function isOnOrderCreated(): bool
    {
        return self::$onOrderCreated;
    }

    /**
     * @param OrderUpdatedEvent $event
     */
    public static function onOrderUpdated(OrderUpdatedEvent $event)
    {
        assert($event);

        static::$onOrderUpdated = true;
    }

    /**
     * @return bool
     */
    public static function isOnOrderUpdated(): bool
    {
        return self::$onOrderUpdated;
    }
}
