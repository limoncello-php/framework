<?php declare(strict_types=1);

namespace Limoncello\Validation\Rules\Generic;

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

use Limoncello\Validation\Contracts\Errors\ErrorCodes;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\I18n\Messages;
use Limoncello\Validation\Rules\ExecuteRule;
use function assert;
use function in_array;

/**
 * @package Limoncello\Validation
 */
final class Enum extends ExecuteRule
{
    /**
     * Property key.
     */
    private const PROPERTY_VALUES = self::PROPERTY_LAST + 1;

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        assert(!empty($values));

        parent::__construct([
            static::PROPERTY_VALUES => $values,
        ]);
    }

    /**
     * @param mixed            $value
     * @param ContextInterface $context
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function execute($value, ContextInterface $context): array
    {
        $values = $context->getProperties()->getProperty(static::PROPERTY_VALUES);
        $isOk   = in_array($value, $values);

        return $isOk === true ?
            static::createSuccessReply($value) :
            static::createErrorReply($context, $value, ErrorCodes::INVALID_VALUE, Messages::INVALID_VALUE, []);
    }
}
