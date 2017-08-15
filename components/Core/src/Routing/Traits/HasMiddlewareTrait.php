<?php namespace Limoncello\Core\Routing\Traits;

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
use LogicException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Core
 *
 * @method string getCallableToCacheMessage();
 */
trait HasMiddlewareTrait
{
    /**
     * @var callable[]
     */
    private $middleware = [];

    /**
     * @param callable[] $middleware
     *
     * @return self
     */
    public function setMiddleware(array $middleware): self
    {
        foreach ($middleware as $item) {
            $isValid = $this->checkPublicStaticCallable($item, [
                ServerRequestInterface::class,
                Closure::class,
                ContainerInterface::class,
            ], ResponseInterface::class);
            if ($isValid === false) {
                throw new LogicException($this->getCallableToCacheMessage());
            }
        }

        $this->middleware = $middleware;

        return $this;
    }
}
