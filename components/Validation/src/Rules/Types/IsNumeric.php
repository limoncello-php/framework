<?php declare(strict_types=1);

namespace Limoncello\Validation\Rules\Types;

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
use function is_numeric;

/**
 * @package Limoncello\Validation
 */
final class IsNumeric extends ExecuteRule
{
    /**
     * @param mixed            $value
     * @param ContextInterface $context
     * @param null             $primaryKeyValue
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function execute($value, ContextInterface $context, $primaryKeyValue = null): array
    {
        return is_numeric($value) === true ?
            static::createSuccessReply($value) :
            static::createErrorReply($context, $value, ErrorCodes::IS_NUMERIC, Messages::IS_NUMERIC, []);
    }
}
