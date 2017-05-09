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
use Limoncello\Events\Contracts\EventHandlerInterface;
use Limoncello\Events\Contracts\EventInterface;
use Limoncello\Events\SimpleEventEmitter;
use Limoncello\Events\Traits\SelectClassesTrait;
use ReflectionClass;
use ReflectionMethod;

/**
 * @package Limoncello\Events
 */
abstract class EventSettings implements SettingsInterface
{
    use SelectClassesTrait;

    /**
     * @return string
     */
    abstract protected function getEventsFolder(): string;

    /**
     * @return string
     */
    abstract protected function getSubscribersFolder(): string;

    /** Settings key */
    const KEY_EVENTS_FOLDER = 0;

    /** Settings key */
    const KEY_SUBSCRIBERS_FOLDER = self::KEY_EVENTS_FOLDER + 1;

    /** Settings key */
    const KEY_LAST = self::KEY_SUBSCRIBERS_FOLDER + 1;

    /**
     * @return array
     */
    final public function get(): array
    {
        $emitter        = new SimpleEventEmitter();
        $eventClasses   = iterator_to_array(
            $this->selectClasses($this->getEventsFolder(), EventInterface::class)
        );
        $handlerClasses = iterator_to_array(
            $this->selectClasses($this->getSubscribersFolder(), EventHandlerInterface::class)
        );
        foreach ($this->getEventSubscribers($eventClasses, $handlerClasses) as $eventClass => $subscriber) {
            $emitter->subscribe($eventClass, $subscriber);
        }

        $cacheData = $emitter->getStaticSubscribers();

        return $cacheData;
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
            if ($curReflection->isAbstract() === true) {
                continue;
            }
            if ($eventClass === $curEventClass ||
                $curReflection->isSubclassOf($eventClass) === true ||
                ($reflection->isInterface() === true && $curReflection->implementsInterface($eventClass))
            ) {
                yield $curEventClass;
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
