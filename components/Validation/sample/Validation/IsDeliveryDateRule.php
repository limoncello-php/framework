<?php declare(strict_types=1);

namespace Sample\Validation;

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

use DateTime;
use DateTimeInterface;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\Rules\ExecuteRule;

/**
 * @package Sample
 */
class IsDeliveryDateRule extends ExecuteRule
{
    /** @var string Message Template */
    const MESSAGE_TEMPLATE = 'The value should be a valid delivery date.';

    /**
     * @param mixed            $value
     * @param ContextInterface $context
     * @param null             $primaryKeyValue
     *
     * @return array
     */
    public static function execute($value, ContextInterface $context, $primaryKeyValue = null): array
    {
        $from = new DateTime('tomorrow');
        $to   = new DateTime('+5 days');

        $isValidDeliveryDate = $value instanceof DateTimeInterface === true && $value >= $from && $value <= $to;

        return $isValidDeliveryDate === true ?
            static::createSuccessReply($value) :
            static::createErrorReply(
                $context,
                $value,
                Errors::IS_DELIVERY_DATE,
                static::MESSAGE_TEMPLATE,
                [$from->getTimestamp(), $to->getTimestamp()]
            );
    }
}
