<?php namespace Limoncello\Validation\Rules;

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
use Limoncello\Validation\Contracts\MessageCodes;

/**
 * @package Limoncello\Validation
 */
class Required extends BaseRule
{
    /**
     * @var bool
     */
    private $hasBeenInvoked = false;

    /**
     * @inheritdoc
     */
    public function validate($input): Generator
    {
        $this->hasBeenInvoked = true;

        // yield empty Generator to comply with interface
        foreach ([] as $item) {
            yield $item; // @codeCoverageIgnore
        }
    }

    /**
     * @inheritdoc
     */
    public function isStateless(): bool
    {
        return parent::isStateless() && false;
    }

    /**
     * @inheritdoc
     */
    public function onFinish(ErrorAggregatorInterface $aggregator)
    {
        parent::onFinish($aggregator);

        if ($this->hasBeenInvoked === false) {
            $aggregator->add($this->createError($this->getParameterName(), null, MessageCodes::REQUIRED));
        }
    }
}
