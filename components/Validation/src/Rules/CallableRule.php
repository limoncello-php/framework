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
use Limoncello\Validation\Contracts\MessageCodes;

/**
 * @package Limoncello\Validation
 */
class CallableRule extends BaseRule
{
    /**
     * @var callable
     */
    private $toCall;

    /**
     * @var int
     */
    private $messageCode;

    /**
     * @param callable $callable
     * @param int      $messageCode
     */
    public function __construct(callable $callable, int $messageCode = MessageCodes::INVALID_VALUE)
    {
        $this->toCall      = $callable;
        $this->messageCode = $messageCode;
    }

    /**
     * @inheritdoc
     */
    public function validate($input): Generator
    {
        $result = call_user_func($this->toCall, $input);
        if ($result !== true) {
            $error = $this->createError($this->getParameterName(), $input, $this->messageCode);
            yield $error;
        }
    }
}
