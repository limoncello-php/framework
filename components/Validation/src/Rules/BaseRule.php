<?php namespace Limoncello\Validation\Rules;

/**
 * Copyright 2015-2016 info@neomerx.com (www.neomerx.com)
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

use Limoncello\Validation\Contracts\ErrorAggregatorInterface;
use Limoncello\Validation\Contracts\ErrorInterface;
use Limoncello\Validation\Contracts\MessageCodes;
use Limoncello\Validation\Contracts\RuleInterface;
use Limoncello\Validation\Errors\Error;

/**
 * @package Limoncello\Validation
 */
abstract class BaseRule implements RuleInterface
{
    /**
     * @var null|string
     */
    private $parameterName = null;

    /**
     * @var null|RuleInterface
     */
    private $parentRule = null;

    /**
     * @inheritdoc
     */
    public function isStateless(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function onFinish(ErrorAggregatorInterface $aggregator)
    {
    }

    /**
     * @inheritdoc
     */
    public function setParentRule(RuleInterface $parent)
    {
        $this->parentRule = $parent;
    }

    /**
     * @inheritdoc
     */
    public function getParameterName()
    {
        if ($this->parameterName !== null) {
            return $this->parameterName;
        }

        if (($parentRule = $this->getParentRule()) !== null) {
            return $parentRule->getParameterName();
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function setParameterName(string $parameterName = null): RuleInterface
    {
        $this->parameterName = $parameterName;

        return $this;
    }

    /**
     * @return RuleInterface|null
     */
    protected function getParentRule()
    {
        return $this->parentRule;
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @param int    $code
     * @param mixed  $context
     *
     * @return ErrorInterface
     */
    protected function createError($name, $value, $code = MessageCodes::INVALID_VALUE, $context = null)
    {
        return new Error($name, $value, $code, $context);
    }
}
