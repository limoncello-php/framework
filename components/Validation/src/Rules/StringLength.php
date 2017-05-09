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
class StringLength extends BaseRule
{
    /** Error context key */
    const CONTEXT_MIN = 0;

    /** Error context key */
    const CONTEXT_MAX = 1;

    /**
     * @var int|null
     */
    private $min;

    /**
     * @var int|null
     */
    private $max;

    /**
     * @param int|null $min
     * @param int|null $max
     */
    public function __construct(int $min = null, int $max = null)
    {
        $this->min = $min;
        $this->max = $max;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function validate($input): Generator
    {
        $length = strlen($input);

        $lowerLimitFailed = $this->min !== null && $length < $this->min;
        $upperLimitFailed = $this->max !== null && $length > $this->max;
        $isMinMaxSet      = $this->min !== null && $this->max !== null;

        if ($isMinMaxSet && ($lowerLimitFailed === true || $upperLimitFailed === true)) {
            $context = [static::CONTEXT_MIN => $this->min, static::CONTEXT_MAX => $this->max];
            yield $this->createError($this->getParameterName(), $input, MessageCodes::STRING_LENGTH, $context);
        } elseif ($lowerLimitFailed === true && $upperLimitFailed === false) {
            $context = [static::CONTEXT_MIN => $this->min, static::CONTEXT_MAX => $this->max];
            yield $this->createError($this->getParameterName(), $input, MessageCodes::STRING_LENGTH_MIN, $context);
        } elseif ($lowerLimitFailed === false && $upperLimitFailed === true) {
            $context = [static::CONTEXT_MIN => $this->min, static::CONTEXT_MAX => $this->max];
            yield $this->createError($this->getParameterName(), $input, MessageCodes::STRING_LENGTH_MAX, $context);
        }
    }
}
