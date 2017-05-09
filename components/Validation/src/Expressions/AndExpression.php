<?php namespace Limoncello\Validation\Expressions;

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

/**
 * @package Limoncello\Validation
 */
class AndExpression extends BaseExpression
{
    /**
     * @var RuleInterface
     */
    private $first;

    /**
     * @var RuleInterface
     */
    private $second;

    /**
     * @param RuleInterface $first
     * @param RuleInterface $second
     */
    public function __construct(RuleInterface $first, RuleInterface $second)
    {
        $this->first  = $first;
        $this->second = $second;

        $first->setParentRule($this);
        $second->setParentRule($this);
    }

    /**
     * @inheritdoc
     */
    public function validate($input): Generator
    {
        $firstHasErrors = false;
        foreach ($this->first->validate($input) as $error) {
            $firstHasErrors = true;
            yield $error;
        }

        if ($firstHasErrors === false) {
            foreach ($this->second->validate($input) as $error) {
                yield $error;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function isStateless(): bool
    {
        return $this->first->isStateless() === true && $this->second->isStateless() === true;
    }

    /**
     * @inheritdoc
     */
    public function onFinish(ErrorAggregatorInterface $aggregator)
    {
        $errorsBeforeFirst = count($aggregator->get());
        $this->first->onFinish($aggregator);
        $errorsAfterFirst = count($aggregator->get());

        if ($errorsAfterFirst === $errorsBeforeFirst) {
            $this->second->onFinish($aggregator);
        }
    }
}
