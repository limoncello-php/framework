<?php declare(strict_types=1);

namespace Limoncello\Validation\Contracts\Errors;

/**
 * Copyright 2015-2020 info@neomerx.com
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

/**
 * @package Limoncello\Validation
 */
interface ErrorCodes
{
    // Generic

    /** Message code */
    const INVALID_VALUE = 0;

    /** Message code */
    const REQUIRED = self::INVALID_VALUE + 1;

    // Types

    /** Message code */
    const IS_STRING = self::REQUIRED + 1;

    /** Message code */
    const IS_BOOL = self::IS_STRING + 1;

    /** Message code */
    const IS_INT = self::IS_BOOL + 1;

    /** Message code */
    const IS_FLOAT = self::IS_INT + 1;

    /** Message code */
    const IS_NUMERIC = self::IS_FLOAT + 1;

    /** Message code */
    const IS_DATE_TIME = self::IS_NUMERIC + 1;

    /** Message code */
    const IS_ARRAY = self::IS_DATE_TIME + 1;

    // Comparisons

    /** Message code */
    const DATE_TIME_BETWEEN = self::IS_ARRAY + 1;

    /** Message code */
    const DATE_TIME_EQUALS = self::DATE_TIME_BETWEEN + 1;

    /** Message code */
    const DATE_TIME_LESS_OR_EQUALS = self::DATE_TIME_EQUALS + 1;

    /** Message code */
    const DATE_TIME_LESS_THAN = self::DATE_TIME_LESS_OR_EQUALS + 1;

    /** Message code */
    const DATE_TIME_MORE_OR_EQUALS = self::DATE_TIME_LESS_THAN + 1;

    /** Message code */
    const DATE_TIME_MORE_THAN = self::DATE_TIME_MORE_OR_EQUALS + 1;

    /** Message code */
    const DATE_TIME_NOT_EQUALS = self::DATE_TIME_MORE_THAN + 1;

    /** Message code */
    const NUMERIC_BETWEEN = self::DATE_TIME_NOT_EQUALS + 1;

    /** Message code */
    const NUMERIC_LESS_OR_EQUALS = self::NUMERIC_BETWEEN + 1;

    /** Message code */
    const NUMERIC_LESS_THAN = self::NUMERIC_LESS_OR_EQUALS + 1;

    /** Message code */
    const NUMERIC_MORE_OR_EQUALS = self::NUMERIC_LESS_THAN + 1;

    /** Message code */
    const NUMERIC_MORE_THAN = self::NUMERIC_MORE_OR_EQUALS + 1;

    /** Message code */
    const SCALAR_EQUALS = self::NUMERIC_MORE_THAN + 1;

    /** Message code */
    const SCALAR_NOT_EQUALS = self::SCALAR_EQUALS + 1;

    /** Message code */
    const SCALAR_IN_VALUES = self::SCALAR_NOT_EQUALS + 1;

    /** Message code */
    const STRING_LENGTH_BETWEEN = self::SCALAR_IN_VALUES + 1;

    /** Message code */
    const STRING_LENGTH_MIN = self::STRING_LENGTH_BETWEEN + 1;

    /** Message code */
    const STRING_LENGTH_MAX = self::STRING_LENGTH_MIN + 1;

    /** Message code */
    const STRING_REG_EXP = self::STRING_LENGTH_MAX + 1;

    /** Message code */
    const IS_NULL = self::STRING_REG_EXP + 1;

    /** Message code */
    const IS_NOT_NULL = self::IS_NULL + 1;

    // Special code for those who extend this enum

    /** Message code */
    const LAST = self::IS_NOT_NULL;
}
