<?php namespace Limoncello\Validation\Errors;

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

use Limoncello\Validation\Contracts\Errors\ErrorAggregatorInterface;
use Limoncello\Validation\Contracts\Errors\ErrorInterface;

/**
 * @package Limoncello\Validation
 */
class ErrorAggregator implements ErrorAggregatorInterface
{
    /**
     * @var ErrorInterface[]
     */
    private $errors;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->clear();
    }

    /**
     * @inheritdoc
     */
    public function add(ErrorInterface $error): ErrorAggregatorInterface
    {
        $this->errors[] = $error;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function get(): array
    {
        return $this->errors;
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        return count($this->get());
    }

    /**
     * @inheritdoc
     */
    public function clear(): ErrorAggregatorInterface
    {
        $this->errors = [];

        return $this;
    }
}
