<?php namespace Limoncello\Flute\Contracts\Validation;

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

use Neomerx\JsonApi\Contracts\Document\ErrorInterface;

/**
 * @package Limoncello\Flute
 */
interface JsonApiDataParserInterface
{
    /**
     * @param array $jsonData
     *
     * @return bool
     */
    public function parse(array $jsonData): bool;

    /**
     * @param array $jsonData
     *
     * @return self
     */
    public function assert(array $jsonData): self;

    /**
     * @param string $index
     * @param string $name
     * @param array  $jsonData
     *
     * @return bool
     */
    public function parseRelationship(string $index, string $name, array $jsonData): bool;

    /**
     * @param string $index
     * @param string $name
     * @param array  $jsonData
     *
     * @return self
     */
    public function assertRelationship(string $index, string $name, array $jsonData): self;

    /**
     * @return array
     */
    public function getCaptures(): array;

    /**
     * @return ErrorInterface[]
     */
    public function getErrors(): array;
}
