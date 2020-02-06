<?php declare(strict_types=1);

namespace Limoncello\Validation\Blocks;

/**
 * Copyright 2015-2020 info@neomerx.com
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

use Limoncello\Validation\Contracts\Blocks\ExecutionBlockInterface;
use Limoncello\Validation\Contracts\Blocks\OrExpressionInterface;

/**
 * @package Limoncello\Validation
 */
final class OrBlock implements OrExpressionInterface
{
    /**
     * @var ExecutionBlockInterface
     */
    private $primary;

    /**
     * @var ExecutionBlockInterface
     */
    private $secondary;

    /**
     * @var array
     */
    private $properties;

    /**
     * @param ExecutionBlockInterface $primary
     * @param ExecutionBlockInterface $secondary
     * @param array                   $properties
     */
    public function __construct(
        ExecutionBlockInterface $primary,
        ExecutionBlockInterface $secondary,
        array $properties = []
    ) {
        $this->primary    = $primary;
        $this->secondary  = $secondary;
        $this->properties = $properties;
    }

    /**
     * @inheritdoc
     */
    public function getPrimary(): ExecutionBlockInterface
    {
        return $this->primary;
    }

    /**
     * @inheritdoc
     */
    public function getSecondary(): ExecutionBlockInterface
    {
        return $this->secondary;
    }

    /**
     * @inheritdoc
     */
    public function getProperties(): array
    {
        return $this->properties;
    }
}
