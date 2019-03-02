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

use DateTimeInterface;
use Limoncello\Validation\Contracts\Errors\ContextKeys;
use Limoncello\Validation\Contracts\Errors\ErrorCodes;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use function assert;

/**
 * @package Limoncello\Validation
 */
final class DateTimeLessOrEquals extends BaseOneValueComparision
{
    /**
     * @param DateTimeInterface $value
     */
    public function __construct(DateTimeInterface $value)
    {
        parent::__construct(
            $value->getTimestamp(),
            ErrorCodes::DATE_TIME_LESS_OR_EQUALS,
            [ContextKeys::DATE_TIME_VALUE => $value->getTimestamp()]
        );
    }

    /**
     * @inheritdoc
     */
    public static function compare($value, ContextInterface $context): bool
    {
        assert($value instanceof DateTimeInterface);
        $result = $value instanceof DateTimeInterface && $value->getTimestamp() <= static::readValue($context);

        return $result;
    }
}
