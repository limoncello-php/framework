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

use Generator;
use Limoncello\Validation\Contracts\ErrorAggregatorInterface;
use Limoncello\Validation\Contracts\MessageCodes;

/**
 * @package Limoncello\Validation
 */
class Fail extends BaseRule
{
    /**
     * @var bool
     */
    private $errorAdded = false;

    /**
     * @var int
     */
    private $messageCode;

    /**
     * @param int $messageCode
     */
    public function __construct(int $messageCode = MessageCodes::INVALID_VALUE)
    {
        $this->messageCode = $messageCode;
    }

    /**
     * @inheritdoc
     */
    public function validate($input): Generator
    {
        $this->errorAdded = true;
        yield $this->createError($this->getParameterName(), $input, $this->messageCode);
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

        if ($this->errorAdded === false) {
            $aggregator->add($this->createError($this->getParameterName(), null, $this->messageCode));
        }
    }
}
