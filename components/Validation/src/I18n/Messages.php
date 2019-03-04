<?php declare(strict_types=1);

namespace Limoncello\Validation\I18n;

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

/**
 * @package Limoncello\Validation
 */
interface Messages
{
    /** @var string Default message template */
    const INVALID_VALUE = 'The value is invalid.';

    /** @var string Default message template */
    const REQUIRED = 'The value is required.';

    /** @var string Default message template */
    const IS_STRING = 'The value should be a string.';

    /** @var string Default message template */
    const IS_BOOL = 'The value should be a boolean.';

    /** @var string Default message template */
    const IS_INT = 'The value should be an integer.';

    /** @var string Default message template */
    const IS_FLOAT = 'The value should be a float.';

    /** @var string Default message template */
    const IS_NUMERIC = 'The value should be a numeric.';

    /** @var string Default message template */
    const IS_DATE_TIME = 'The value should be a valid date time.';

    /** @var string Default message template */
    const IS_ARRAY = 'The value should be an array.';

    /** @var string Default message template */
    const DATE_TIME_BETWEEN = 'The date time value should be between {0} and {1}.';

    /** @var string Default message template */
    const DATE_TIME_EQUALS = 'The date time value should be equal to {0}.';

    /** @var string Default message template */
    const DATE_TIME_LESS_OR_EQUALS = 'The date time value should be less or equal to {0}.';

    /** @var string Default message template */
    const DATE_TIME_LESS_THAN = 'The date time value should be less than {0}.';

    /** @var string Default message template */
    const DATE_TIME_MORE_OR_EQUALS = 'The date time value should be more or equal to {0}.';

    /** @var string Default message template */
    const DATE_TIME_MORE_THAN = 'The date time value should be more than {0}.';

    /** @var string Default message template */
    const DATE_TIME_NOT_EQUALS = 'The date time value should not be equal to {0}.';

    /** @var string Default message template */
    const NUMERIC_BETWEEN = 'The value should be between {0} and {1}.';

    /** @var string Default message template */
    const NUMERIC_LESS_OR_EQUALS = 'The value should be be less or equal to {0}.';

    /** @var string Default message template */
    const NUMERIC_LESS_THAN = 'The value should be be less than {0}.';

    /** @var string Default message template */
    const NUMERIC_MORE_OR_EQUALS = 'The value should be be more or equal to {0}.';

    /** @var string Default message template */
    const NUMERIC_MORE_THAN = 'The value should be be more than {0}.';

    /** @var string Default message template */
    const SCALAR_EQUALS = 'The value should be equal to {0}.';

    /** @var string Default message template */
    const SCALAR_NOT_EQUALS = 'The value should not be equal to {0}.';

    /** @var string Default message template */
    const SCALAR_IN_VALUES = 'The value is invalid.';

    /** @var string Default message template */
    const STRING_LENGTH_BETWEEN = 'The value should be between {0} and {1} characters.';

    /** @var string Default message template */
    const STRING_LENGTH_MIN = 'The value should be at least {0} characters.';

    /** @var string Default message template */
    const STRING_LENGTH_MAX = 'The value should not be greater than {0} characters.';

    /** @var string Default message template */
    const STRING_REG_EXP = 'The value format is invalid.';

    /** @var string Default message template */
    const IS_NULL = 'The value should be NULL.';

    /** @var string Default message template */
    const IS_NOT_NULL = 'The value should not be NULL.';
}
