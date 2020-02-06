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
use function is_bool;
use function is_string;
use function strtolower;

/**
 * @package Limoncello\Validation
 */
final class StringToBool extends ExecuteRule
{
    /**
     * @param mixed            $value
     * @param ContextInterface $context
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public static function execute($value, ContextInterface $context): array
    {
        if (is_string($value) === true) {
            $lcValue = strtolower($value);
            if ($lcValue === 'true' || $lcValue === '1' || $lcValue === 'on' || $lcValue === 'yes') {
                $reply = static::createSuccessReply(true);
            } elseif ($lcValue === 'false' || $lcValue === '0' || $lcValue === 'off' || $lcValue === 'no') {
                $reply = static::createSuccessReply(false);
            } else {
                $reply = static::createErrorReply($context, $value, ErrorCodes::IS_BOOL, Messages::IS_BOOL, []);
            }
        } elseif (is_bool($value) === true) {
            $reply = static::createSuccessReply($value);
        } else {
            $reply = static::createErrorReply($context, $value, ErrorCodes::IS_BOOL, Messages::IS_BOOL, []);
        }

        return $reply;
    }
}
