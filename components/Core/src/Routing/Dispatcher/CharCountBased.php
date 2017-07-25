<?php namespace Limoncello\Core\Routing\Dispatcher;

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

use Limoncello\Contracts\Routing\DispatcherInterface;
use Limoncello\Core\Routing\Traits\DispatcherTrait;

/**
 * @package Limoncello\Core
 */
class CharCountBased extends \FastRoute\Dispatcher\CharCountBased implements DispatcherInterface
{
    use DispatcherTrait;

    /** @noinspection PhpMissingParentConstructorInspection
     * Constructor.
     */
    public function __construct()
    {
        assert($this->areCodeValid() === true);

        // Suppress parent constructor. Use `setData` instead.
    }

    /**
     * @inheritdoc
     */
    public function setData(array $data): void
    {
        list($this->staticRouteMap, $this->variableRouteData) = $data;
    }
}
