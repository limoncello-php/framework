<?php namespace Limoncello\Tests\Events\Data\Subscribers;

/**
 * Copyright 2015-2017 info@neomerx.com
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
use Limoncello\Tests\Events\Data\Events\CreatedEvent;
use Limoncello\Tests\Events\Data\Events\UpdatedEvent;

/**
 * @package Limoncello\Tests\Events
 */
class GenericSubscribers implements EventHandlerInterface
{
    /**
     * @var bool
     */
    private static $onCreated = false;

    /**
     * @var bool
     */
    private static $onUpdated = false;

    /**
     * @return void
     */
    public static function reset()
    {
        static::$onCreated = false;
        static::$onUpdated = false;
    }

    /**
     * @param CreatedEvent $event
     */
    public static function onCreated(CreatedEvent $event)
    {
        assert($event);

        static::$onCreated = true;
    }

    /**
     * @return boolean
     */
    public static function isOnCreated(): bool
    {
        return self::$onCreated;
    }

    /**
     * @param UpdatedEvent $event
     */
    public static function onUpdated(UpdatedEvent $event)
    {
        assert($event);

        static::$onUpdated = true;
    }

    /**
     * @return boolean
     */
    public static function isOnUpdated(): bool
    {
        return self::$onUpdated;
    }
}
