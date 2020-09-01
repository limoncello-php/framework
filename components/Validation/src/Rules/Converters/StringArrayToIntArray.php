<?php declare(strict_types=1);

namespace Limoncello\Validation\Rules\Converters;

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
use function is_iterable;
use function is_numeric;
use function is_string;

/**
 * @package Limoncello\Validation
 */
final class StringArrayToIntArray extends ExecuteRule
{
    /**
     * @param mixed            $value
     * @param ContextInterface $context
     * @param null             $primaryKeyValue
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public static function execute($value, ContextInterface $context, $primaryKeyValue = null): array
    {
        $reply = null;

        $result = [];
        if (is_iterable($value) === true) {
            foreach ($value as $key => $mightBeString) {
                if (is_string($mightBeString) === true || is_numeric($mightBeString) === true) {
                    $result[$key] = (int)$mightBeString;
                } else {
                    $reply = static::createErrorReply(
                        $context,
                        $mightBeString,
                        ErrorCodes::IS_STRING,
                        Messages::IS_STRING,
                        []
                    );
                    break;
                }
            }
        } else {
            $reply = static::createErrorReply($context, $value, ErrorCodes::IS_ARRAY, Messages::IS_ARRAY, []);
        }

        return $reply !== null ? $reply : static::createSuccessReply($result);
    }
}
