<?php namespace Limoncello\Core\Routing\Traits;

/**
 * Copyright 2015-2018 info@neomerx.com
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
use FastRoute\Dispatcher;
use Limoncello\Contracts\Routing\DispatcherInterface;

/**
 * @package Limoncello\Core
 *
 * @method dispatch(string $method, string $uri): array
 */
trait DispatcherTrait
{
    /**
     * Validate implementation code match with FasRoute ones.
     *
     * @return bool
     */
    protected function areCodeValid(): bool
    {
        return
            Dispatcher::NOT_FOUND === DispatcherInterface::ROUTE_NOT_FOUND &&
            Dispatcher::FOUND === DispatcherInterface::ROUTE_FOUND &&
            Dispatcher::METHOD_NOT_ALLOWED === DispatcherInterface::ROUTE_METHOD_NOT_ALLOWED;
    }

    /**
     * @inheritdoc
     */
    public function dispatchRequest(string $method, string $uri): array
    {
        return $this->dispatch($method, $uri);
    }
}
