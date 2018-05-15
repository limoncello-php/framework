<?php namespace Limoncello\Validation\Contracts\Captures;

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

use Countable;

/**
 * @package Limoncello\Validation
 */
interface CaptureAggregatorInterface extends Countable
{
    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return self
     */
    public function remember(string $key, $value): self;

    /**
     * @return array
     */
    public function get(): array;

    /**
     * @return self
     */
    public function clear(): self;
}
