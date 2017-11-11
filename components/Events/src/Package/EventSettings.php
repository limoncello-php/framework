<?php namespace Limoncello\Events\Package;

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

use Generator;
use Limoncello\Contracts\Settings\SettingsInterface;
use Limoncello\Core\Reflection\ClassIsTrait;
use Limoncello\Events\Contracts\EventHandlerInterface;
use Limoncello\Events\Contracts\EventInterface;
use Limoncello\Events\SimpleEventEmitter;
use ReflectionClass;
use ReflectionMethod;

/**
 * @package Limoncello\Events
 */
abstract class EventSettings implements SettingsInterface
{
    use ClassIsTrait;

    /** Settings key */
    const KEY_EVENTS_FOLDER = 0;

    /** Settings key */
    const KEY_EVENTS_FILE_MASK = self::KEY_EVENTS_FOLDER + 1;

    /** Settings key */
    const KEY_SUBSCRIBERS_FOLDER = self::KEY_EVENTS_FILE_MASK + 1;

    /** Settings key */
    const KEY_SUBSCRIBERS_FILE_MASK = self::KEY_SUBSCRIBERS_FOLDER + 1;

    /** Settings key */
    const KEY_CACHED_DATA = self::KEY_SUBSCRIBERS_FILE_MASK + 1;

    /** Settings key */
    const KEY_LAST = self::KEY_CACHED_DATA;

    /**
     * @return array
     */
    final public function get(array $appConfig): array
    {
        $defaults = $this->getSettings();

        $eventsFolder = $defaults[static::KEY_EVENTS_FOLDER] ?? null;
        assert(
            $eventsFolder !== null && empty(glob($eventsFolder)) === false,
            "Invalid Events folder `$eventsFolder`."
        );

        $eventsFileMask = $defaults[static::KEY_EVENTS_FILE_MASK] ?? null;
        assert(empty($eventsFileMask) === false, "Invalid Events file mask `$eventsFileMask`.");

        $subscribersFolder = $defaults[static::KEY_SUBSCRIBERS_FOLDER] ?? null;
        assert(
            $subscribersFolder !== null && empty(glob($subscribersFolder)) === false,
            "Invalid Subscribers folder `$subscribersFolder`."
        );

        $subscribersFileMask = $defaults[static::KEY_SUBSCRIBERS_FILE_MASK] ?? null;
        assert(empty($subscribersFileMask) === false, "Invalid Subscribers file mask `$subscribersFileMask`.");

        $eventsPath      = $eventsFolder . DIRECTORY_SEPARATOR . $eventsFileMask;
        $subscribersPath = $subscribersFolder . DIRECTORY_SEPARATOR . $subscribersFileMask;

        $emitter        = new SimpleEventEmitter();
        $eventClasses   = iterator_to_array(
            $this->selectClasses($eventsPath, EventInterface::class)
        );
        $handlerClasses = iterator_to_array(
            $this->selectClasses($subscribersPath, EventHandlerInterface::class)
        );
        foreach ($this->getEventSubscribers($eventClasses, $handlerClasses) as $eventClass => $subscriber) {
            $emitter->subscribe($eventClass, $subscriber);
        }

        $cacheData = $emitter->getStaticSubscribers();

        return $defaults + [static::KEY_CACHED_DATA => $cacheData];
    }

    /**
     * @return array
     */
    protected function getSettings(): array
    {
        return [
            static::KEY_EVENTS_FILE_MASK      => '*.php',
            static::KEY_SUBSCRIBERS_FILE_MASK => '*.php',
        ];
    }

    /**
     * @param array $eventClasses
     * @param array $handlerClasses
     *
     * @return Generator
     */
    private function getEventSubscribers(array $eventClasses, array $handlerClasses): Generator
    {
        foreach ($handlerClasses as $handlerClass) {
            foreach ($this->selectEvenHandlers($handlerClass) as $eventClass => $subscriber) {
                foreach ($this->getChildEvents($eventClass, $eventClasses) as $childEventClass) {
                    yield $childEventClass => $subscriber;
                }
            }
        }
    }

    /**
     * @param string $handlerClass
     *
     * @return Generator
     */
    private function selectEvenHandlers(string $handlerClass): Generator
    {
        $reflection = new ReflectionClass($handlerClass);
        foreach ($reflection->getMethods(ReflectionMethod::IS_STATIC | ReflectionMethod::IS_PUBLIC) as $method) {
            if ($this->isEventHandlerMethod($method) === true) {
                $eventClass = $method->getParameters()[0]->getClass()->getName();
                $subscriber = [$handlerClass, $method->getName()];
                yield $eventClass => $subscriber;
            }
        }
    }

    /**
     * @param string $eventClass
     * @param array  $eventClasses
     *
     * @return Generator
     */
    private function getChildEvents(string $eventClass, array $eventClasses): Generator
    {
        $reflection = new ReflectionClass($eventClass);
        foreach ($eventClasses as $curEventClass) {
            $curReflection = new ReflectionClass($curEventClass);
            if ($curReflection->isAbstract() === false) {
                if ($eventClass === $curEventClass ||
                    $curReflection->isSubclassOf($eventClass) === true ||
                    ($reflection->isInterface() === true && $curReflection->implementsInterface($eventClass))
                ) {
                    yield $curEventClass;
                }
            }
        }
    }

    /**
     * @param ReflectionMethod $method
     *
     * @return bool
     */
    private function isEventHandlerMethod(ReflectionMethod $method): bool
    {
        $result =
            $method->isPublic() === true &&
            $method->isStatic() === true &&
            count($params = $method->getParameters()) === 1 &&
            $params[0]->getClass()->implementsInterface(EventInterface::class) === true;

        return $result;
    }
}
