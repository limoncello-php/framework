<?php namespace Limoncello\Flute\Contracts\Validation;

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

use Limoncello\Flute\Validation\ErrorCollection;

/**
 * @package Limoncello\Flute
 */
interface ValidatorInterface
{
    /**
     * @param array $jsonData
     *
     * @return bool
     */
    public function check(array $jsonData): bool;

    /**
     * @param array $jsonData
     *
     * @return self
     */
    public function assert(array $jsonData): self;

    /**
     * @return array
     */
    public function getCaptures(): array;

    /**
     * @return ErrorCollection
     */
    public function getErrors(): ErrorCollection;
}
