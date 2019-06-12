<?php declare(strict_types=1);

namespace Limoncello\Events;

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

use Closure;
use Limoncello\Events\Contracts\EventDispatcherInterface;
use Limoncello\Events\Contracts\EventEmitterInterface;
use Limoncello\Events\Contracts\EventInterface;
use Limoncello\Events\Exceptions\EventNotFoundException;
use ReflectionException;
use ReflectionMethod;
use function assert;
use function array_filter;
use function array_key_exists;
use function call_user_func_array;
use function count;
use function explode;
use function get_class;
use function is_array;
use function is_string;

/**
 * @package Limoncello\Events
 */
class SimpleEventEmitter implements EventEmitterInterface, EventDispatcherInterface
{
    /**
     * All events known to system with or without corresponding event handler.
     *
     * @var array
     */
    private $allowedEvents = [];

    /**
     * @var array
     */
    private $subscribers = [];

    /**
     * @var bool
     */
    private $cancellingEnabled = false;

    /**
     * @inheritdoc
     */
    public function emit(string $eventName, array $arguments = []): void
    {
        $this->isCancellingEnabled() === true ?
            $this->emitWithCancellingPropagationCheck($eventName, $arguments) :
            $this->emitWithoutCancellingPropagationCheck($eventName, $arguments);
    }

    /**
     * @inheritdoc
     */
    public function dispatch(EventInterface $event): void
    {
        $this->emit(get_class($event), [$event]);
    }

    /**
     * @param string $eventName
     *
     * @return SimpleEventEmitter
     */
    public function addAllowedEvent(string $eventName): self
    {
        $this->allowedEvents[$eventName] = true;

        return $this;
    }

    /**
     * @param string[] $eventNames
     *
     * @return SimpleEventEmitter
     */
    public function addAllowedEvents(array $eventNames): self
    {
        foreach ($eventNames as $eventName) {
            $this->addAllowedEvent($eventName);
        }

        return $this;
    }

    /**
     * @param string   $eventName
     * @param callable $subscriber
     *
     * @return self
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function subscribe(string $eventName, callable $subscriber): self
    {
        if ($subscriber instanceof Closure || ($staticMethod = $this->parseStaticMethod($subscriber)) === null) {
            $this->subscribers[$eventName][] = $subscriber;
        } else {
            assert($staticMethod !== null);
            $this->subscribers[$eventName][] = $this->getUnifiedStaticMethodRepresentation($staticMethod);
        }

        $this->addAllowedEvent($eventName);

        return $this;
    }

    /**
     * @param string   $eventName
     * @param callable $subscriber
     *
     * @return self
     */
    public function unSubscribe(string $eventName, callable $subscriber): self
    {
        if (($subscriber instanceof Closure) === false &&
            ($staticMethod = $this->parseStaticMethod($subscriber)) !== null
        ) {
            $subscriber = $this->getUnifiedStaticMethodRepresentation($staticMethod);
        }

        $eventSubscribers = $this->getEventSubscribers($eventName);
        $eventSubscribers = array_filter($eventSubscribers, function ($curSubscriber) use ($subscriber) {
            return $curSubscriber !== $subscriber;
        });

        return $this->setEventSubscribers($eventName, $eventSubscribers);
    }

    /**
     * @return bool
     */
    public function isCancellingEnabled(): bool
    {
        return $this->cancellingEnabled;
    }

    /**
     * @return self
     */
    public function enableCancelling(): self
    {
        $this->cancellingEnabled = true;

        return $this;
    }

    /**
     * @return self
     */
    public function disableCancelling(): self
    {
        $this->cancellingEnabled = false;

        return $this;
    }

    /**
     * @param array $data
     *
     * @return self
     */
    public function setData(array $data): self
    {
        assert(count($data) == 2);

        [$allowedEvents, $subscribers] = $data;

        assert($this->checkAllSubscribersAreStatic($subscribers) === true);

        return $this->setAllowedEvents($allowedEvents)->setSubscribers($subscribers);
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        $subscribers = [];

        foreach ($this->getSubscribers() as $eventName => $subscribersList) {
            $eventSubscribers = [];
            foreach ($subscribersList as $subscriber) {
                if (($staticMethod = $this->parseStaticMethod($subscriber)) !== null) {
                    $eventSubscribers[] = $this->getUnifiedStaticMethodRepresentation($staticMethod);
                }
            }

            if (empty($eventSubscribers) === false) {
                $subscribers[$eventName] = $eventSubscribers;
            }
        }

        $data = [$this->getAllowedEvents(), $subscribers];

        return $data;
    }

    /**
     * @param string $eventName
     *
     * @return bool
     */
    protected function isEventAllowed(string $eventName): bool
    {
        return array_key_exists($eventName, $this->allowedEvents);
    }

    /**
     * @return array
     */
    protected function getAllowedEvents(): array
    {
        return $this->allowedEvents;
    }

    /**
     * @param array $allowedEvents
     *
     * @return self
     */
    protected function setAllowedEvents(array $allowedEvents): self
    {
        $this->allowedEvents = $allowedEvents;

        return $this;
    }

    /**
     * @return array
     */
    protected function getSubscribers(): array
    {
        return $this->subscribers;
    }

    /**
     * @param callable[] $subscribers
     *
     * @return self
     */
    protected function setSubscribers(array $subscribers): self
    {
        $this->subscribers = $subscribers;

        return $this;
    }

    /**
     * @param string $eventName
     * @param array  $arguments
     *
     * @return void
     */
    protected function emitWithoutCancellingPropagationCheck(string $eventName, array $arguments = []): void
    {
        foreach ($this->getEventSubscribers($eventName) as $subscriber) {
            call_user_func_array($subscriber, $arguments);
        }
    }

    /**
     * @param string $eventName
     * @param array  $arguments
     *
     * @return void
     */
    protected function emitWithCancellingPropagationCheck(string $eventName, array $arguments = []): void
    {
        foreach ($this->getEventSubscribers($eventName) as $subscriber) {
            if (call_user_func_array($subscriber, $arguments) === false) {
                break;
            }
        }
    }

    /**
     * @param string $eventName
     *
     * @return array
     */
    private function getEventSubscribers(string $eventName): array
    {
        if ($this->isEventAllowed($eventName) === false) {
            throw new EventNotFoundException($eventName);
        }

        $result = $this->getSubscribers()[$eventName] ?? [];

        return $result;
    }

    /**
     * @param string     $eventName
     * @param callable[] $eventSubscribers
     *
     * @return self
     */
    private function setEventSubscribers(string $eventName, array $eventSubscribers): self
    {
        $this->subscribers[$eventName] = $eventSubscribers;

        return $this;
    }

    /**
     * This debugging function checks subscribers are
     * [
     *     ...
     *     'string_event_name' => [static callable, static callable, ...],
     *     ...
     * ]
     *
     * @param array $subscribers
     *
     * @return bool
     */
    private function checkAllSubscribersAreStatic(array $subscribers): bool
    {
        $result = true;
        foreach ($subscribers as $eventName => $callableList) {
            if (is_string($eventName) === false || is_array($callableList) === false) {
                $result = false;
                break;
            }
            foreach ($callableList as $mightBeCallable) {
                $method = $this->parseStaticMethod($mightBeCallable);
                if ($method === null || $method->isStatic() === false) {
                    $result = false;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @param $mightBeCallable
     *
     * @return null|ReflectionMethod
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function parseStaticMethod($mightBeCallable): ?ReflectionMethod
    {
        // static callable could be in form of 'ClassName::methodName' or ['ClassName', 'methodName']
        if (is_string($mightBeCallable) === true &&
            count($mightBeCallablePair = explode('::', $mightBeCallable, 2)) === 2
        ) {
            list ($mightBeClassName, $mightBeMethodName) = $mightBeCallablePair;
        } elseif (is_array($mightBeCallable) === true && count($mightBeCallable) === 2) {
            list ($mightBeClassName, $mightBeMethodName) = $mightBeCallable;
        } else {
            return null;
        }

        try {
            $reflectionMethod = new ReflectionMethod($mightBeClassName, $mightBeMethodName);
        } catch (ReflectionException $exception) {
            return null;
        }

        if ($reflectionMethod->isStatic() === false) {
            return null;
        }

        return $reflectionMethod;
    }

    /**
     * @param ReflectionMethod $staticMethod
     *
     * @return callable
     */
    private function getUnifiedStaticMethodRepresentation(ReflectionMethod $staticMethod): callable
    {
        return [$staticMethod->class, $staticMethod->name];
    }
}
