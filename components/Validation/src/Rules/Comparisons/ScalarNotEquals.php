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

use Limoncello\Validation\Contracts\Errors\ContextKeys;
use Limoncello\Validation\Contracts\Errors\ErrorCodes;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use function assert;
use function is_scalar;

/**
 * @package Limoncello\Validation
 */
final class ScalarNotEquals extends BaseOneValueComparision
{
    /**
     * @param mixed $value
     */
    public function __construct($value)
    {
        assert(static::isValidType($value) === true);
        parent::__construct($value, ErrorCodes::SCALAR_NOT_EQUALS, [ContextKeys::SCALAR_VALUE => $value]);
    }

    /**
     * @inheritdoc
     */
    public static function compare($value, ContextInterface $context): bool
    {
        assert(static::isValidType($value) === true);
        $result = static::isValidType($value) === true && $value !== static::readValue($context);

        return $result;
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    private static function isValidType($value): bool
    {
        return is_scalar($value) === true;
    }
}
