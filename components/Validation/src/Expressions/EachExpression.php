<?php namespace Limoncello\Validation\Expressions;

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

use Generator;
use Limoncello\Validation\Contracts\ErrorAggregatorInterface;
use Limoncello\Validation\Contracts\RuleInterface;

/**
 * @package Limoncello\Validation
 */
class EachExpression extends BaseExpression
{
    /**
     * @var RuleInterface
     */
    private $rule;

    /**
     * @param RuleInterface $rule
     */
    public function __construct(RuleInterface $rule)
    {
        $rule->setParentRule($this);
        $this->rule = $rule;
    }

    /**
     * @inheritdoc
     */
    public function validate($input): Generator
    {
        foreach ($input as $value) {
            foreach ($this->getRule()->validate($value) as $error) {
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
    public function onFinish(ErrorAggregatorInterface $aggregator)
    {
        $this->getRule()->onFinish($aggregator);
    }

    /**
     * @return RuleInterface
     */
    protected function getRule(): RuleInterface
    {
        return $this->rule;
    }
}
