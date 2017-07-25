<?php namespace Limoncello\Events;

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

use Closure;
use Limoncello\Events\Contracts\EventDispatcherInterface;
use Limoncello\Events\Contracts\EventEmitterInterface;
use Limoncello\Events\Contracts\EventInterface;
use Limoncello\Events\Exceptions\EventNotFoundException;
use ReflectionException;
use ReflectionMethod;

/**
 * @package Limoncello\Events
 */
class SimpleEventEmitter implements EventEmitterInterface, EventDispatcherInterface
{
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
     * @param array $subscribers
     *
     * @return self
     */
    public function setStaticSubscribers(array $subscribers): self
    {
        assert($this->checkAllSubscribersAreStatic($subscribers) === true);

        return $this->setSubscribers($subscribers);
    }

    /**
     * @return array
     */
    public function getStaticSubscribers(): array
    {
        $result = [];

        foreach ($this->getSubscribers() as $eventName => $subscribersList) {
            $eventSubscribers = [];
            foreach ($subscribersList as $subscriber) {
                if (($staticMethod = $this->parseStaticMethod($subscriber)) !== null) {
                    $eventSubscribers[] = $this->getUnifiedStaticMethodRepresentation($staticMethod);
                }
            }

            if (empty($eventSubscribers) === false) {
                $result[$eventName] = $eventSubscribers;
            }
        }

        return $result;
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
        $subscribers = $this->getSubscribers();
        if (array_key_exists($eventName, $subscribers) === false) {
            throw new EventNotFoundException($eventName);
        }

        $result = $subscribers[$eventName];

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
    private function parseStaticMethod($mightBeCallable)
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
