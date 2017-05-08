<?php namespace Limoncello\Container;

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

use InvalidArgumentException;
use Limoncello\Container\Exceptions\NotFoundException;
use Limoncello\Contracts\Container\ContainerInterface;

/**
 * @package Limoncello\Container
 */
class Container extends \Pimple\Container implements ContainerInterface
{
    /**
     * @inheritdoc
     */
    public function get($identity)
    {
        try {
            return $this->offsetGet($identity);
        } catch (InvalidArgumentException $exception) {
            throw new NotFoundException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * @inheritdoc
     */
    public function has($identity)
    {
        return $this->offsetExists($identity);
    }
}
