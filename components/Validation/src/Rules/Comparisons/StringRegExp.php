<?php declare(strict_types=1);

namespace Limoncello\Validation\Rules\Comparisons;

/**
 * Copyright 2015-2019 info@neomerx.com
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

use Limoncello\Validation\Contracts\Errors\ErrorCodes;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\I18n\Messages;
use function assert;
use function is_string;
use function preg_match;

/**
 * @package Limoncello\Validation
 */
final class StringRegExp extends BaseOneValueComparision
{
    /**
     * @param mixed $pattern
     */
    public function __construct($pattern)
    {
        assert(is_string($pattern) === true);
        parent::__construct(
            $pattern,
            ErrorCodes::STRING_REG_EXP,
            Messages::STRING_REG_EXP,
            [$pattern]
        );
    }

    /**
     * @inheritdoc
     */
    public static function compare($value, ContextInterface $context): bool
    {
        assert(is_string($value) === true);
        $result = is_string($value) === true && preg_match(static::readValue($context), $value) === 1;

        return $result;
    }
}
