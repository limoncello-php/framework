<?php declare(strict_types=1);

namespace Limoncello\Auth\Authorization\PolicyAdministration;

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

use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\AllOfInterface;
use function assert;

/**
 * @package Limoncello\Auth
 */
class AllOf implements AllOfInterface
{
    /**
     * @var array
     */
    private $pairs;

    /**
     * @param array $pairs
     */
    public function __construct(array $pairs)
    {
        assert(empty($pairs) === false);

        $this->pairs = $pairs;
    }

    /**
     * @inheritdoc
     */
    public function getPairs(): array
    {
        return $this->pairs;
    }
}
