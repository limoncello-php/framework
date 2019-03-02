<?php declare(strict_types=1);

namespace Sample\Validation;

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

use DateTime;
use DateTimeInterface;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\Rules\ExecuteRule;

/**
 * @package Sample
 */
class IsDeliveryDateRule extends ExecuteRule
{
    /**
     * @param mixed            $value
     * @param ContextInterface $context
     *
     * @return array
     */
    public static function execute($value, ContextInterface $context): array
    {
        $isValidDeliveryDate =
            $value instanceof DateTimeInterface === true &&
            $value >= new DateTime('tomorrow') &&
            $value <= new DateTime('+5 days');

        return $isValidDeliveryDate === true ?
            static::createSuccessReply($value) :
            static::createErrorReply($context, $value, Errors::IS_DELIVERY_DATE);
    }
}
