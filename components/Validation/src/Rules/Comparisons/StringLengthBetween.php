<?php namespace Limoncello\Validation\Rules\Comparisons;

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

use Limoncello\Validation\Contracts\Errors\ContextKeys;
use Limoncello\Validation\Contracts\Errors\ErrorCodes;
use Limoncello\Validation\Contracts\Execution\ContextInterface;

/**
 * @package Limoncello\Validation
 */
final class StringLengthBetween extends BaseTwoValueComparision
{
    /**
     * @param int $min
     * @param int $max
     */
    public function __construct($min, $max)
    {
        assert(is_scalar($min) === true && is_scalar($max) === true);
        $errorContext = [
            ContextKeys::STRING_LENGTH_MIN => $min,
            ContextKeys::STRING_LENGTH_MAX => $max,
        ];
        parent::__construct($min, $min, ErrorCodes::STRING_LENGTH_BETWEEN, $errorContext);
    }

    /**
     * @inheritdoc
     */
    public static function compare($value, ContextInterface $context): bool
    {
        assert(is_string($value) === true);
        $result =
            is_string($value) === true &&
            static::readLowerValue($context) <= ($length = strlen($value)) &&
            $length <= static::readUpperValue($context);

        return $result;
    }
}
