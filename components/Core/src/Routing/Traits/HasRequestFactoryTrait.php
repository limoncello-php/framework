<?php namespace Limoncello\Core\Routing\Traits;

/**
 * Copyright 2015-2016 info@neomerx.com (www.neomerx.com)
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

use Limoncello\Core\Application\Application;
use LogicException;

/**
 * @package Limoncello\Core
 *
 * @method bool   isCallableToCache($value);
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
     * @return $this
     */
    public function setRequestFactory(callable $requestFactory = null)
    {
        if ($requestFactory !== null && $this->isCallableToCache($requestFactory) === false) {
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
    protected function getDefaultRequestFactory()
    {
        return Application::getDefaultRequestFactory();
    }
}
