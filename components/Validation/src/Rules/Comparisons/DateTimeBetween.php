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
use Limoncello\Validation\Contracts\Errors\ErrorCodes;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\I18n\Messages;
use function assert;

/**
 * @package Limoncello\Validation
 */
final class DateTimeBetween extends BaseTwoValueComparision
{
    /**
     * @param DateTimeInterface $lowerValue
     * @param DateTimeInterface $upperValue
     */
    public function __construct(DateTimeInterface $lowerValue, DateTimeInterface $upperValue)
    {
        assert($lowerValue <= $upperValue);

        parent::__construct(
            $lowerValue->getTimestamp(),
            $upperValue->getTimestamp(),
            ErrorCodes::DATE_TIME_BETWEEN,
            Messages::DATE_TIME_BETWEEN,
            [$lowerValue->getTimestamp(), $upperValue->getTimestamp()]
        );
    }

    /**
     * @inheritdoc
     */
    public static function compare($value, ContextInterface $context): bool
    {
        assert($value instanceof DateTimeInterface);
        $result =
            $value instanceof DateTimeInterface &&
            static::readLowerValue($context) <= ($timestamp = $value->getTimestamp()) &&
            $timestamp <= static::readUpperValue($context);

        return $result;
    }
}
