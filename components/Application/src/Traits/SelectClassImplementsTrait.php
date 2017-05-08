<?php namespace Limoncello\Application\Traits;

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

use Generator;

/**
 * @package Limoncello\Application
 */
trait SelectClassImplementsTrait
{
    /**
     * @param string[] $classNames
     * @param string   $interfaceName
     *
     * @return Generator
     */
    protected function selectClassImplements(array $classNames, string $interfaceName): Generator
    {
        foreach ($classNames as $className) {
            if (array_key_exists($interfaceName, class_implements($className)) === true) {
                yield $className;
            }
        }
    }
}
