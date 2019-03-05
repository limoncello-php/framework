<?php declare(strict_types=1);

namespace Limoncello\Validation;

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

use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\Validator\Comparisons;
use Limoncello\Validation\Validator\Converters;
use Limoncello\Validation\Validator\Generics;
use Limoncello\Validation\Validator\Types;
use function assert;

/**
 * @package Limoncello\Validation
 */
class Rules
{
    /**
     * Callable for using in `ifX`.
     */
    public const IS_NULL_CALLABLE = [self::class, 'isNull'];

    /**
     * Callable for using in `ifX`.
     */
    public const IS_EMPTY_CALLABLE = [self::class, 'isEmpty'];

    use Comparisons {
        equals as public;
        notEquals as public;
        inValues as public;
        lessThan as public;
        lessOrEquals as public;
        moreThan as public;
        moreOrEquals as public;
        between as public;
        stringLengthBetween as public;
        stringLengthMin as public;
        stringLengthMax as public;
        regexp as public;
        nullable as public;
    }

    use Converters {
        stringToBool as public;
        stringToDateTime as public;
        stringToFloat as public;
        stringToInt as public;
        stringToArray as public;
        stringArrayToIntArray as public;
    }

    use Generics {
        andX as public;
        orX as public;
        ifX as public;
        success as public;
        fail as public;
        value as public;
        required as public;
        enum as public;
        filter as public;
    }

    use Types {
        isArray as public;
        isString as public;
        isBool as public;
        isInt as public;
        isFloat as public;
        isNumeric as public;
        isDateTime as public;
    }

    /**
     * @param mixed            $value
     * @param ContextInterface $context
     *
     * @return bool
     */
    public static function isNull($value, ContextInterface $context): bool
    {
        assert($context);

        return $value === null;
    }

    /**
     * @param mixed            $value
     * @param ContextInterface $context
     *
     * @return bool
     */
    public static function isEmpty($value, ContextInterface $context): bool
    {
        assert($context);

        return empty($value);
    }
}
