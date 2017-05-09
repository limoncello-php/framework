<?php namespace Limoncello\Validation\Converters;

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
use Limoncello\Validation\Contracts\ErrorAggregatorInterface;
use Limoncello\Validation\Contracts\RuleInterface;
use Limoncello\Validation\Errors\Error;

/**
 * @package Limoncello\Validation
 */
abstract class BaseConverter implements RuleInterface
{
    /**
     * @var RuleInterface
     */
    private $rule;

    /**
     * @param mixed $input
     *
     * @return bool
     */
    abstract protected function convert($input): bool;

    /**
     * @return int
     */
    abstract protected function getErrorCode(): int;

    /**
     * @return mixed
     */
    abstract protected function getErrorContext();

    /**
     * @return mixed
     */
    abstract protected function getConverted();

    /**
     * @param RuleInterface $next
     */
    public function __construct(RuleInterface $next)
    {
        $this->rule = $next;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function validate($input): Generator
    {
        if ($this->convert($input) === false) {
            yield new Error($this->getParameterName(), $input, $this->getErrorCode(), $this->getErrorContext());
        } else {
            foreach ($this->getRule()->validate($this->getConverted()) as $error) {
                yield $error;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function isStateless(): bool
    {
        return $this->getRule()->isStateless();
    }

    /**
     * @inheritdoc
     */
    public function setParentRule(RuleInterface $parent)
    {
        $this->getRule()->setParentRule($parent);
    }

    /**
     * @inheritdoc
     */
    public function getParameterName()
    {
        return $this->getRule()->getParameterName();
    }

    /**
     * @inheritdoc
     */
    public function setParameterName(string $parameterName = null): RuleInterface
    {
        $this->getRule()->setParameterName($parameterName);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function onFinish(ErrorAggregatorInterface $aggregator)
    {
        $this->getRule()->onFinish($aggregator);
    }

    /**
     * @return RuleInterface
     */
    protected function getRule()
    {
        return $this->rule;
    }
}
