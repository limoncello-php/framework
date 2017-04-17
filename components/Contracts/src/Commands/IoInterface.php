<?php namespace Limoncello\Contracts\Commands;

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

/**
 * @package Limoncello\Contracts
 */
interface IoInterface
{
    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasArgument(string $name): bool;

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getArgument(string $name);

    /**
     * @return array
     */
    public function getArguments(): array;

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasOption(string $name): bool;

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getOption(string $name);

    /**
     * @return array
     */
    public function getOptions(): array;

    /**
     * @param string $message
     *
     * @return self
     */
    public function writeInfo(string $message): self;

    /**
     * @param string $message
     *
     * @return self
     */
    public function writeWarning(string $message): self;

    /**
     * @param string $message
     *
     * @return self
     */
    public function writeError(string $message): self;
}
