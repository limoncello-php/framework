<?php namespace Limoncello\Validation\Validator;

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

use Limoncello\Validation\Captures\CaptureAggregator;
use Limoncello\Validation\Contracts\Captures\CaptureAggregatorInterface;
use Limoncello\Validation\Contracts\Errors\ErrorAggregatorInterface;
use Limoncello\Validation\Contracts\ValidatorInterface;
use Limoncello\Validation\Errors\ErrorAggregator;

/**
 * @package Limoncello\Validation
 */
abstract class BaseValidator implements ValidatorInterface
{
    /**
     * @var bool
     */
    private $areAggregatorsDirty = false;

    /**
     * @var CaptureAggregatorInterface
     */
    private $captures;

    /**
     * @var ErrorAggregatorInterface
     */
    private $errors;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->resetAggregators();
    }

    /**
     * @inheritdoc
     */
    public function getCaptures(): array
    {
        return $this->getCaptureAggregator()->get();
    }

    /**
     * @inheritdoc
     */
    public function getErrors(): array
    {
        return $this->getErrorAggregator()->get();
    }

    /**
     * @return CaptureAggregatorInterface
     */
    protected function getCaptureAggregator(): CaptureAggregatorInterface
    {
        return $this->captures;
    }

    /**
     * @return ErrorAggregatorInterface
     */
    protected function getErrorAggregator(): ErrorAggregatorInterface
    {
        return $this->errors;
    }

    /**
     * @return CaptureAggregatorInterface
     */
    protected function createCaptureAggregator(): CaptureAggregatorInterface
    {
        return new CaptureAggregator();
    }

    /**
     * @return ErrorAggregatorInterface
     */
    protected function createErrorAggregator(): ErrorAggregatorInterface
    {
        return new ErrorAggregator();
    }

    /**
     * @return bool
     */
    protected function areAggregatorsDirty(): bool
    {
        return $this->areAggregatorsDirty;
    }

    /**
     * @return self
     */
    protected function markAggregatorsAsDirty(): self
    {
        $this->areAggregatorsDirty = true;

        return $this;
    }

    /**
     * @return self
     */
    protected function resetAggregators(): self
    {
        $this->captures = $this->createCaptureAggregator();
        $this->errors   = $this->createErrorAggregator();

        $this->areAggregatorsDirty = false;

        return $this;
    }
}
