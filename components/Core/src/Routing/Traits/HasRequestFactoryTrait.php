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

use Limoncello\Contracts\Core\SapiInterface;
use Limoncello\Core\Application\Application;
use LogicException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Core
 *
 * @method string getCallableToCacheMessage();
 */
trait HasRequestFactoryTrait
{
    /**
     * @var callable|false|null
     */
    private $requestFactory = false;

    /**
     * @param callable|null $requestFactory
     *
     * @return self
     */
    public function setRequestFactory(callable $requestFactory = null): self
    {
        $parameters = [SapiInterface::class, ContainerInterface::class];
        if ($requestFactory !== null &&
            $this->checkPublicStaticCallable($requestFactory, $parameters, ServerRequestInterface::class) === false
        ) {
            throw new LogicException($this->getCallableToCacheMessage());
        }
        $this->requestFactory = $requestFactory;

        return $this;
    }

    /**
     * @return bool
     */
    protected function isRequestFactorySet()
    {
        return $this->requestFactory !== false;
    }

    /**
     * @return callable
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function getDefaultRequestFactory(): callable
    {
        return Application::getDefaultRequestFactory();
    }
}
