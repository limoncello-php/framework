<?php namespace Limoncello\Flute\Validation\Execution;

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

use Limoncello\Flute\Contracts\Validation\ContextStorageInterface;
use Limoncello\Validation\Execution\ContextStorage as BaseContextStorage;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Flute
 */
class ContextStorage extends BaseContextStorage implements ContextStorageInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     * @param array              $blocks
     */
    public function __construct(ContainerInterface $container, array $blocks)
    {
        parent::__construct($blocks);
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
